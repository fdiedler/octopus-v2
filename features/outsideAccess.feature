Feature: Control outside access 

  Background:
    Given I have a user "checkerParis@wehost.fr" with role "ROLE_CHECKER"
      And I have a user "checkerBordeaux@wehost.fr" with role "ROLE_CHECKER"
      
      And I have a market "Paris"
      And I have a market "Bordeaux"
      And I have a market "Biarritz"
      
  Scenario: Check access for a given checker
      Given The user "checkerParis@wehost.fr" is logged
      Then The access to "test" action from controller "controller" should be "boulet"
      