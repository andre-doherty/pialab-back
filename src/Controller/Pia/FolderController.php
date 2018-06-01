<?php

/*
 * Copyright (C) 2015-2018 Libre Informatique
 *
 * This file is licenced under the GNU LGPL v3.
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace PiaApi\Controller\Pia;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations as FOSRest;
use PiaApi\Entity\Pia\Folder;

class FolderController extends RestController
{
    /**
     * @FOSRest\Get("/folders")
     * @Security("is_granted('ROLE_PIA_LIST')")
     *
     * @return array
     */
    public function listAction(Request $request)
    {
        $this->canAccessRouteOr304();

        $structure = $this->getUser()->getStructure();
        $collection = $this->getRepository()->findBy(['structure' => $structure, 'parent' => null]);

        return $this->view($collection, Response::HTTP_OK);
    }

    /**
     * @FOSRest\Get("/folders/{id}")
     * @Security("is_granted('ROLE_PIA_VIEW')")
     *
     * @return array
     */
    public function showAction(Request $request, $id)
    {
        $this->canAccessRouteOr304();

        $folder = $this->getRepository()->find($id);
        if ($folder === null) {
            return $this->view($folder, Response::HTTP_NOT_FOUND);
        }

        $this->canAccessResourceOr304($folder);

        return $this->view($folder, Response::HTTP_OK);
    }

    /**
     * @FOSRest\Post("/folders")
     * @Security("is_granted('ROLE_PIA_CREATE')")
     *
     * @return array
     */
    public function createAction(Request $request)
    {
        $this->canAccessRouteOr304();

        if (($parentId = $request->get('parent_id')) === null) {
            return $this->view('Missing parent id', Response::HTTP_BAD_REQUEST);
        }

        $parent = $this->getRepository()->find($parentId);

        $folder = $this->newFromRequest($request);
        $folder->setStructure($this->getUser()->getStructure());
        $folder->setParent($parent);
        $this->persist($folder);

        return $this->view($folder, Response::HTTP_OK);
    }

    /**
     * @FOSRest\Put("/folders/{id}", requirements={"id"="\d+"})
     * @FOSRest\Post("/folders/{id}", requirements={"id"="\d+"})
     * @Security("is_granted('ROLE_PIA_EDIT')")
     *
     * @return array
     */
    public function updateAction(Request $request, $id)
    {
        $this->canAccessRouteOr304();

        $folder = $this->newFromRequest($request);
        $this->canAccessResourceOr304($folder);
        $this->update($folder);

        return $this->view($folder, Response::HTTP_OK);
    }

    /**
     * @FOSRest\Delete("/folders/{id}", requirements={"id"="\d+"})
     * @Security("is_granted('ROLE_PIA_DELETE')")
     *
     * @return array
     */
    public function deleteAction(Request $request, $id)
    {
        $this->canAccessRouteOr304();

        $folder = $this->getRepository()->find($id);
        $this->canAccessResourceOr304($folder);
        $this->remove($folder);

        return $this->view($folder, Response::HTTP_OK);
    }

    protected function getEntityClass()
    {
        return Folder::class;
    }

    public function canAccessResourceOr304($resource): void
    {
        if (!$resource instanceof Folder) {
            throw new AccessDeniedHttpException();
        }

        if ($resource->getStructure() !== $this->getUser()->getStructure()) {
            throw new AccessDeniedHttpException();
        }
    }
}