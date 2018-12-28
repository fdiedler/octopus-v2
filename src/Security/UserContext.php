<?php

namespace App\Security;

/**
 * @author DIEDLER Florent <florent@wehost.fr>
 */
class UserContext
{
    // List of all market ids allowed for this user
    private $allowedMarketId;
    
    // Current user email
    private $currentUserEmail;
    
    public function __construct(?array $_allowedMarket, string $userEmail)
    {
        $this->allowedMarketId = ($_allowedMarket != null ? $_allowedMarket : []);
        $this->currentUserEmail = $userEmail;
    }
    
    public function getAllowedMarketId() : array
    {
        return $this->allowedMarketId;
    }
    
    public function isSuperAdmin() : bool
    {
        return count($this->getAllowedMarketId()) == 0;
    }
    
    public function getCurrentUser() : string
    {
        return $this->currentUserEmail;
    }
}