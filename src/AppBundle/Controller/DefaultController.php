<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Email;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $email = new Email();

        $form = $this->createFormBuilder($email, [
                    'attr'=>[
                        'id'=>'emailForm',
                    ],
                    'action' => $this->generateUrl('homepage'),
                    'csrf_protection' => false
                ])
                ->add('email', null, [
                    'label' => 'Email: ',
                    'attr' => [
                        'placeholder' => 'jack@gmail.com',
                    ]
                ])
                ->add('save', SubmitType::class)
                ->getForm();

        $form->handleRequest($request);

        $isSw = (bool) $request->headers->get($this->getParameter('headerSW'));

        if ($isSw) {
            // Add some random delay for the demo
            usleep(rand(2000, 4000) * 1000);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $repo = $em->getRepository(Email::class);
            $existing = $repo->findOneBy([
                'email' => $email->getEmail()
            ]);

            if (!$existing) {
                $em->persist($email);
            } else {
                $email = $existing;
            }
            $email->setInserted(new \DateTime());
            $em->flush();
            
            // Send confirmation email with data
            
            $messageCont = array_filter([
                'Thanks for subscribing to PWA Newsletter',
                $existing ? 'You was already existing in the database.' : null,
                'Date of received submission: '.date('Y-m-d H:i:s'),
                $isSw ? 'From Service worker' : 'Directly from browser'
            ]);

            $message = \Swift_Message::newInstance()
                ->setSubject('PWA Newsletter subscription')
                ->setFrom('cedric@nyrodev.com')
                ->setTo($email->getEmail())
                ->setBody(implode("\n", $messageCont), 'text/plain')
            ;
            $this->get('mailer')->send($message);

            if ($isSw) {
                return new Response('ok');
            }
            return $this->redirectToRoute('homepage');
        }

        if ($isSw) {
            return new Response('ko');
        }
        return $this->render('AppBundle::index.html.php', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/sw.js", name="sw")
     */
    public function swAction(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/javascript');

        return $this->render('AppBundle::sw.js.php', [
            'headerSW' => $this->getParameter('headerSW')
        ], $response);
    }

    /**
     * @Route("/favicons/manifest.json", name="manifest")
     */
    public function manifestAction(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        return $this->render('AppBundle::manifest.json.php', [], $response);
    }
}
