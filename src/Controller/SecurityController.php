<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Security\UserContext;

class SecurityController extends Controller
{

    /**
     * @Route("/", name="login")
     */
    public function loginAction(Request $request)
    {
        $user = $this->getUser();
        if ($user instanceof UserInterface) {
            return $this->redirectToRoute('dashboard');
        }

        if($request->query->get('start')) {
            return $this->get('App\Security\GoogleAuthenticator')->startOAuthFlow();
        }

        /** @var AuthenticationException $exception */
        $exception = $this->get('security.authentication_utils')
            ->getLastAuthenticationError();

        return $this->render('login.html.twig', [
            'error' => $exception ? $exception->getMessage() : NULL,
        ]);
    }

    /**
     * @Route("/connect/after", name="afterLogging")
     */
    public function afterLoggingAction(Request $request)
    {
        // Create filter according to logged user
        $allowedMarket = array();
        $user = $this->getUser();
        if ($user != null)
        {
            // Retrieve user's allowed markets
            $allowedMarket = $user->getAllowedMarketId();
        }
         
        // Store user context in session after being logged
        $userContext = new UserContext($allowedMarket, $user->getEmail());
        $this->get('session')->set('userContext', $userContext);
        
        // Redirect to dashboard
        return $this->redirectToRoute('dashboard');
    }
    
    /**
     * @Route("/connect/logout", name="logout")
     */
    public function logoutAction(Request $request)
    {
        // the firewall handles this route
    }

    /**
     * @Route("/connect/check", name="connect_check_login")
     */
    public function checkAction(Request $request)
    {
        // do nothing, it's catched by the guard authenticator
    }

}
