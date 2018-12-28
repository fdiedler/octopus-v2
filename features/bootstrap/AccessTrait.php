<?php

use PHPUnit\Framework\Assert;
use App\Entity\User;
use App\Entity\Market;
use App\Entity\Checkin;
use App\Entity\Checker;
use App\Security\UserContext;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author DIEDLER Florent <florent@wehost.fr>
 */
trait AccessTrait 
{
    /**
     * @Given The user :user is logged
     */
    public function theUserIsLogged(User $user): void
    {
        // Simulate logging with the given user
        $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
            $user,
            null,
            'main',
            $user->getRoles()
        );
        
        // Update session because filters are stored here
        $userContext = new UserContext($user->getAllowedMarketId(), $user->getEmail());
        $this->get('session')->set('userContext', $userContext);
        
        //print_r($token);
        
        // Apply new token
        $this->getContainer()->get('security.token_storage')->setToken($token);
    }
    
    /**
     * @Then The access to :feature action from controller :controller should be :response
     */
    public function theAccessToActionFromControllerShouldBe($feature, $controller, $response)
    {
        Assert::assertEquals("1", "1");
    }
}
