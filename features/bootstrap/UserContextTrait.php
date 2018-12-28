<?php

use PHPUnit\Framework\Assert;
use App\Entity\User;
use App\Entity\Market;
use App\Security\UserContext;

/**
 * @author Benoit Del Basso <benoit.delbasso@mylittleparis.com>
 */
trait UserContextTrait
{

    /**
     * @Transform :user
     */
    public function fixUser($email)
    {
        return $this->find(User::class, $email , "email");
    }

    /**
     * @Given I have a user :email
     */
    public function iHaveAUser(string $email)
    {
        $user = new User();
        $user->setEmail($email);
        $user->setEnabled(true);
        $user->setRoles('ROLE_USER');
        $this->persistAndFlush($user);
    }
    
    /**
     * @Given I have a user :email with role :role
     */
    public function iHaveAUserWithRole(string $email, string $role)
    {
        $user = new User();
        $user->setEmail($email);
        $user->setEnabled(true);
        $user->setRoles($role);
        $this->persistAndFlush($user);
    }

    /**
     * @Given user :user is the manager of :market with a percentage of :percentage percent
     */
    public function userIsTheManagerOfMarket(User $user, Market $market, int $percentage)
    {
        // Store user context in session (similate logging)
        $userContext = new UserContext([$market->getId()], $user->getEmail());
        $allUserContext = $this->get('session')->get('allUserContext', []);
        $allUserContext[] = $userContext;
        $this->get('session')->set('allUserContext', $allUserContext);
        
        $market->setManager($user);
        $market->setManagerPercentage($percentage);
        $this->persistAndFlush($market);
    }
}
