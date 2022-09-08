<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="acad")
     */
    public function indexAction(Request $request)
    {
        $session = $request->getSession();
        $vars = $this->commonVars($session);
        return $this->render('default/acad.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
            'dataModel' => $vars,
        ]);
    }

    /**
     * @Route("/{anything}", name="acad_1")
     */
    public function index1Action(Request $request)
    {
        $session = $request->getSession();
        $vars = $this->commonVars($session);
        return $this->render('default/acad.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
            'dataModel' => $vars,
        ]);
    }

    /**
     * @Route("/{anything}/{x}", name="acad_2")
     */
    public function index2Action(Request $request)
    {
        $session = $request->getSession();
        $vars = $this->commonVars($session);
        return $this->render('default/acad.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
            'dataModel' => $vars,
        ]);
    }

    protected function commonVars($session, $personId = null)
    {
        if ($personId) {
            $session->set('person_id', $personId);
        } else {
            $personId = $session->get('person_id') ?: 1;
        }

        return [
            'personId' => $session->get('person_id') ?: 1,
        ];
    }
}
