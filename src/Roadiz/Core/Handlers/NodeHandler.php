<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file NodeHandler.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Handlers;

use Doctrine\Common\Collections\Criteria;
use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesCustomForms;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodesToNodes;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Repositories\NodeRepository;
use RZ\Roadiz\Utils\Node\NodeDuplicator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Handle operations with nodes entities.
 */
class NodeHandler extends AbstractHandler
{
    /** @var null|Node  */
    private $node;

    /**
     * @return Node
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @param Node $node
     * @return NodeHandler
     */
    public function setNode(Node $node)
    {
        $this->node = $node;
        return $this;
    }

    /**
     * Remove every node to custom-forms associations for a given field.
     *
     * @param NodeTypeField $field
     * @param bool $flush
     * @return $this
     */
    public function cleanCustomFormsFromField(NodeTypeField $field, $flush = true)
    {
        $nodesCustomForms = $this->objectManager
            ->getRepository(NodesCustomForms::class)
            ->findBy(['node' => $this->node, 'field' => $field]);

        foreach ($nodesCustomForms as $ncf) {
            $this->objectManager->remove($ncf);
        }

        if (true === $flush) {
            $this->objectManager->flush();
        }

        return $this;
    }

    /**
     * Add a node to current custom-forms for a given node-type field.
     *
     * @param CustomForm $customForm
     * @param NodeTypeField $field
     * @param bool $flush
     * @param null|integer $position
     * @return $this
     */
    public function addCustomFormForField(CustomForm $customForm, NodeTypeField $field, $flush = true, $position = null)
    {
        $ncf = new NodesCustomForms($this->node, $customForm, $field);

        if (null === $position) {
            $latestPosition = $this->objectManager
                ->getRepository(NodesCustomForms::class)
                ->getLatestPosition($this->node, $field);
            $ncf->setPosition($latestPosition + 1);
        } else {
            $ncf->setPosition($position);
        }

        $this->objectManager->persist($ncf);

        if (true === $flush) {
            $this->objectManager->flush();
        }

        return $this;
    }

    /**
     * Get custom forms linked to current node for a given fieldname.
     *
     * @param string $fieldName Name of the node-type field
     * @return array
     */
    public function getCustomFormsFromFieldName($fieldName)
    {
        return $this->objectManager
            ->getRepository(CustomForm::class)
            ->findByNodeAndFieldName($this->node, $fieldName);
    }

    /**
     * Remove every node to node associations for a given field.
     *
     * @param \RZ\Roadiz\Core\Entities\NodeTypeField $field
     *
     * @param bool $flush
     * @return $this
     */
    public function cleanNodesFromField(NodeTypeField $field, $flush = true)
    {
        $nodesToNodes = $this->objectManager
            ->getRepository(NodesToNodes::class)
            ->findBy(['nodeA' => $this->node, 'field' => $field]);

        foreach ($nodesToNodes as $ntn) {
            $this->objectManager->remove($ntn);
        }

        if (true === $flush) {
            $this->objectManager->flush();
        }

        return $this;
    }

    /**
     * Add a node to current node for a given node-type field.
     *
     * @param Node $node
     * @param NodeTypeField $field
     * @param bool $flush
     * @param null|integer $position
     * @return $this
     */
    public function addNodeForField(Node $node, NodeTypeField $field, $flush = true, $position = null)
    {
        $ntn = new NodesToNodes($this->node, $node, $field);

        if (null === $position) {
            $latestPosition = $this->objectManager
                ->getRepository(NodesToNodes::class)
                ->getLatestPosition($this->node, $field);
            $ntn->setPosition($latestPosition + 1);
        } else {
            $ntn->setPosition($position);
        }

        $this->objectManager->persist($ntn);
        if (true === $flush) {
            $this->objectManager->flush();
        }

        return $this;
    }

    /**
     * Get nodes linked to current node for a given fieldname.
     *
     * @param string $fieldName Name of the node-type field
     *
     * @return Node[]
     */
    public function getNodesFromFieldName($fieldName)
    {
        return $this->getRepository()
            ->findByNodeAndFieldName(
                $this->node,
                $fieldName
            );
    }

    /**
     * Get nodes reversed-linked to current node for a given fieldname.
     *
     * @param string $fieldName Name of the node-type field
     * @return Node[]
     */
    public function getReverseNodesFromFieldName($fieldName)
    {
        return $this->getRepository()
            ->findByReverseNodeAndFieldName(
                $this->node,
                $fieldName
            );
    }

    /**
     * Get node source by translation.
     *
     * @param Translation $translation
     *
     * @return null|object|NodesSources
     */
    public function getNodeSourceByTranslation($translation)
    {
        return $this->objectManager
            ->getRepository(NodesSources::class)
            ->findOneBy(["node" => $this->node, "translation" => $translation]);
    }

    /**
     * Remove only current node children.
     *
     * @return $this
     */
    private function removeChildren()
    {
        /** @var Node $node */
        foreach ($this->node->getChildren() as $node) {
            $handler = new NodeHandler($this->objectManager);
            $handler->setNode($node);
            $handler->removeWithChildrenAndAssociations();
        }

        return $this;
    }
    /**
     * Remove only current node associations.
     *
     * @return $this
     */
    public function removeAssociations()
    {
        /** @var NodesSources $ns */
        foreach ($this->node->getNodeSources() as $ns) {
            $this->objectManager->remove($ns);
        }

        return $this;
    }
    /**
     * Remove current node with its children recursively and
     * its associations.
     *
     * This method DOES NOT flush objectManager
     *
     * @return $this
     */
    public function removeWithChildrenAndAssociations()
    {
        $this->removeChildren();
        $this->removeAssociations();
        $this->objectManager->remove($this->node);

        return $this;
    }

    /**
     * Soft delete node and its children.
     *
     * **This method does not flush!**
     *
     * @return $this
     */
    public function softRemoveWithChildren()
    {
        $this->node->setStatus(Node::DELETED);

        /** @var Node $node */
        foreach ($this->node->getChildren() as $node) {
            $handler = new NodeHandler($this->objectManager);
            $handler->setNode($node);
            $handler->softRemoveWithChildren();
        }

        return $this;
    }

    /**
     * Un-delete node and its children.
     *
     * **This method does not flush!**
     *
     * @return $this
     */
    public function softUnremoveWithChildren()
    {
        $this->node->setStatus(Node::PENDING);

        /** @var Node $node */
        foreach ($this->node->getChildren() as $node) {
            $handler = new NodeHandler($this->objectManager);
            $handler->setNode($node);
            $handler->softUnremoveWithChildren();
        }

        return $this;
    }

    /**
     * Publish node and its children.
     *
     * **This method does not flush!**
     *
     * @return $this
     */
    public function publishWithChildren()
    {
        /*
         * Publish only if node is Draft or pending
         * NOT deleted nor archived.
         */
        if ($this->node->getStatus() < Node::PUBLISHED) {
            $this->node->setStatus(Node::PUBLISHED);
        }

        /** @var Node $node */
        foreach ($this->node->getChildren() as $node) {
            $handler = new NodeHandler($this->objectManager);
            $handler->setNode($node);
            $handler->publishWithChildren();
        }
        return $this;
    }

    /**
     * Archive node and its children.
     *
     * **This method does not flush!**
     *
     * @return $this
     */
    public function archiveWithChildren()
    {
        $this->node->setStatus(Node::ARCHIVED);

        /** @var Node $node */
        foreach ($this->node->getChildren() as $node) {
            $handler = new NodeHandler($this->objectManager);
            $handler->setNode($node);
            $handler->archiveWithChildren();
        }

        return $this;
    }

    /**
     * Alias for TranslationRepository::findAvailableTranslationsForNode.
     *
     * @deprecated This method has no purpose here.
     * @return Translation[]
     */
    public function getAvailableTranslations()
    {
        return $this->objectManager
            ->getRepository(Translation::class)
            ->findAvailableTranslationsForNode($this->node);
    }
    /**
     * Alias for TranslationRepository::findAvailableTranslationsIdForNode.
     *
     * @deprecated This method has no purpose here.
     * @return array
     */
    public function getAvailableTranslationsId()
    {
        return $this->objectManager
            ->getRepository(Translation::class)
            ->findAvailableTranslationsIdForNode($this->node);
    }

    /**
     * Alias for TranslationRepository::findUnavailableTranslationsForNode.
     *
     * @deprecated This method has no purpose here.
     * @return Translation[]
     */
    public function getUnavailableTranslations()
    {
        return $this->objectManager
            ->getRepository(Translation::class)
            ->findUnavailableTranslationsForNode($this->node);
    }

    /**
     * Alias for TranslationRepository::findUnavailableTranslationIdForNode.
     *
     * @deprecated This method has no purpose here.
     * @return array
     */
    public function findUnavailableTranslationIdForNode()
    {
        return $this->objectManager
            ->getRepository(Translation::class)
            ->findUnavailableTranslationIdForNode($this->node);
    }

    /**
     * Return if is in Newsletter Node.
     *
     * @return bool
     */
    public function isRelatedToNewsletter()
    {
        if ($this->node->getNodeType()->isNewsletterType()) {
            return true;
        }

        $parents = $this->getParents();
        foreach ($parents as $parent) {
            if ($parent->getNodeType()->isNewsletterType()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return if part of Node offspring.
     *
     * @param Node $relative
     *
     * @return bool
     */
    public function isRelatedToNode(Node $relative)
    {
        if ($this->node->getId() === $relative->getId()) {
            return true;
        }

        $parents = $this->getParents();
        foreach ($parents as $parent) {
            if ($parent->getId() === $relative->getId()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return every node’s parents
     * @param TokenStorageInterface|null $tokenStorage
     * @return Node[]
     */
    public function getParents(TokenStorageInterface $tokenStorage = null)
    {
        $parentsArray = [];
        $parent = $this->node;
        $user = null;

        if ($tokenStorage !== null) {
            $user = $tokenStorage->getToken()->getUser();
        }

        do {
            $parent = $parent->getParent();
            if ($parent !== null && !($user !== null && $parent == $user->getChroot())) {
                $parentsArray[] = $parent;
            } else {
                break;
            };
        } while ($parent !== null);

        return array_reverse($parentsArray);
    }

    /**
     * Clean position for current node siblings.
     *
     * Warning, this method does not flush.
     *
     * @param bool $setPositions
     * @return int Return the next position after the **last** node
     */
    public function cleanPositions($setPositions = true)
    {
        if ($this->node->getParent() !== null) {
            $parentHandler = new NodeHandler($this->objectManager);
            $parentHandler->setNode($this->node->getParent());
            return $parentHandler->cleanChildrenPositions($setPositions);
        } else {
            return $this->cleanRootNodesPositions($setPositions);
        }
    }

    /**
     * Reset current node children positions.
     *
     * Warning, this method does not flush.
     *
     * @param bool $setPositions
     * @return int Return the next position after the **last** node
     */
    public function cleanChildrenPositions($setPositions = true)
    {
        /*
         * Force collection to sort on position
         */
        $sort = Criteria::create();
        $sort->orderBy([
            'position' => Criteria::ASC
        ]);

        $children = $this->node->getChildren()->matching($sort);
        $i = 1;
        /** @var Node $child */
        foreach ($children as $child) {
            if ($setPositions) {
                $child->setPosition($i);
            }
            $i++;
        }

        return $i;
    }

    /**
     * Reset every root nodes positions.
     *
     * Warning, this method does not flush.
     *
     * @param bool $setPositions
     * @return int Return the next position after the **last** node
     */
    public function cleanRootNodesPositions($setPositions = true)
    {
        $nodes = $this->getRepository()
            ->setDisplayingNotPublishedNodes(true)
            ->findBy(['parent' => null], ['position' => 'ASC']);

        $i = 1;
        /** @var Node $child */
        foreach ($nodes as $child) {
            if ($setPositions) {
                $child->setPosition($i);
            }
            $i++;
        }

        return $i;
    }

    /**
     * Return all node offspring id.
     * @return array
     */
    public function getAllOffspringId()
    {
        return $this->getRepository()->findAllOffspringIdByNode($this->node);
    }

    /**
     * Set current node as the Home node.
     *
     * @return $this
     */
    public function makeHome()
    {
        $defaults = $this->getRepository()
            ->setDisplayingNotPublishedNodes(true)
            ->findBy(['home' => true]);

        /** @var Node $default */
        foreach ($defaults as $default) {
            $default->setHome(false);
        }
        $this->node->setHome(true);
        $this->objectManager->flush();

        return $this;
    }

    /**
     * Duplicate current node with all its children.
     *
     * @return Node
     * @deprecated Use NodeDuplicator::duplicate() instead.
     */
    public function duplicate()
    {
        $duplicator = new NodeDuplicator($this->node, $this->objectManager);
        return $duplicator->duplicate();
    }

    /**
     * Get previous node from hierarchy.
     *
     * @param  array|null           $criteria
     * @param  array|null           $order
     *
     * @return \RZ\Roadiz\Core\Entities\Node|null
     */
    public function getPrevious(
        array $criteria = null,
        array $order = null
    ) {
        if ($this->node->getPosition() <= 1) {
            return null;
        }
        if (null === $order) {
            $order = [];
        }

        if (null === $criteria) {
            $criteria = [];
        }

        $criteria['parent'] = $this->node->getParent();
        /*
         * Use < operator to get first previous nodeSource
         * even if it’s not the previous position index
         */
        $criteria['position'] = [
            '<',
            $this->node->getPosition(),
        ];

        $order['position'] = 'DESC';

        return $this->getRepository()->findOneBy(
            $criteria,
            $order
        );
    }

    /**
     * Get next node from hierarchy.
     *
     * @param  array|null $criteria
     * @param  array|null $order
     *
     * @return \RZ\Roadiz\Core\Entities\Node|null
     */
    public function getNext(
        array $criteria = null,
        array $order = null
    ) {
        if (null === $criteria) {
            $criteria = [];
        }
        if (null === $order) {
            $order = [];
        }

        $criteria['parent'] = $this->node->getParent();

        /*
         * Use > operator to get first next nodeSource
         * even if it’s not the next position index
         */
        $criteria['position'] = [
            '>',
            $this->node->getPosition(),
        ];
        $order['position'] = 'ASC';

        return $this->getRepository()
            ->findOneBy(
                $criteria,
                $order
            );
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository|NodeRepository
     */
    public function getRepository()
    {
        return $this->objectManager->getRepository(Node::class);
    }
}
