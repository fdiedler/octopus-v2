Feature: User filtering data feature 

  Background:
    Given I have a user "victor@wehost.fr"
      And I have a user "florent@wehost.fr"
      And I have a user "user@wehost.fr"
      And I have a user "admin@wehost.fr"
      
      And I have a market "Paris"
      And I have a market "Bordeaux"
      And I have a market "Biarritz"
      
      And I have a cleaning provider with name "Elegance" in market "Paris"
      And I have a cleaning provider with name "Elegance2" in market "Paris"
      And I have a cleaning provider with name "TestBiarritz" in market "Biarritz"
      
      And I have a host "Benoit" with email "benoit@test.com"
      And I have a host "Celia" with email "celia@test.com"
      And I have a host "Mandy" with email "mandy@test.com"
      And I have a host "Pierre" with email "pierre@test.com"
      
      And I have a property "LOFT-1" for host "Benoit" and market "Paris"
      And I have a property "LOFT-2" for host "Celia" and market "Paris"
      And I have a property "LOFT-2-bis" for host "Celia" and market "Bordeaux"
      And I have a property "LOFT-3" for host "Mandy" and market "Bordeaux"
      And I have a property "LOFT-3-bis" for host "Mandy" and market "Bordeaux"
      
      And the property "LOFT-1" is being managed between "2018-01-01" and "2018-12-31"
      And the property "LOFT-2" is being managed between "2018-01-01" and "2018-12-31"
      And the property "LOFT-2-bis" is being managed between "2018-01-01" and "2018-12-31"
      And the property "LOFT-3" is being managed between "2018-01-01" and "2018-12-31"
      And the property "LOFT-3-bis" is being managed between "2018-01-01" and "2018-12-31"
      
      And user "victor@wehost.fr" is the manager of "Paris" with a percentage of 15 percent
      And user "florent@wehost.fr" is the manager of "Bordeaux" with a percentage of 10 percent
      And user "user@wehost.fr" is the manager of "Biarritz" with a percentage of 5 percent
        
  Scenario: Check properties managed for a given user
      Then User "victor@wehost.fr" should managed 3 hosts and 2 properties
        And User "florent@wehost.fr" should managed 3 hosts and 3 properties
        And User "user@wehost.fr" should managed 1 hosts and 0 properties
        And User "admin@wehost.fr" should managed 4 hosts and 5 properties

  Scenario: Check providers managed for a given user
      Then User "victor@wehost.fr" should managed 2 cleaning providers
        And User "florent@wehost.fr" should managed 0 cleaning providers
        And User "user@wehost.fr" should managed 1 cleaning providers
        And User "admin@wehost.fr" should managed 3 cleaning providers
  
  Scenario: Check reservations, cleanings and extra charges managed for a given user
      Given the property "LOFT-1" has a "confirmed" reservation "RES-1" between "2018-08-05" and "2018-08-11"
        And the property "LOFT-1" has a "canceled" reservation "RES-1-c" between "2018-01-01" and "2018-01-10"
        And the property "LOFT-2" has a "confirmed" reservation "RES-2" between "2018-08-05" and "2018-08-11"
        And the property "LOFT-2-bis" has a "confirmed" reservation "RES-3" between "2018-08-05" and "2018-08-11"
        And the property "LOFT-3" has a "confirmed" reservation "RES-4" between "2018-08-05" and "2018-08-11"
      
        And the reservation "RES-1" has a cleaning of "€33" schedulted the "2018-08-11"
        And the reservation "RES-2" has a cleaning of "€33" schedulted the "2018-08-11"
        And the reservation "RES-3" has a cleaning of "€33" schedulted the "2018-08-11"
      
        And the property "LOFT-1" has an extra charge of "€20" with "€0" taxes on "2018-08-11" for "extracharge for Paris"
        And the property "LOFT-1" has an extra charge of "-€10.5" with "€0" taxes on "2018-08-11" for "extracharge for Paris"
        And the property "LOFT-3-bis" has an extra charge of "-€15.5" with "€0" taxes on "2018-08-11" for "extracharge for Bordeaux"
        
      Then User "victor@wehost.fr" should managed 3 reservations and 2 cleanings with 2 extracharges
        And User "victor@wehost.fr" should managed 2 confirmed reservations between "2018-01-01" and "2018-12-31"
        
        And User "florent@wehost.fr" should managed 2 reservations and 1 cleanings with 1 extracharges
        And User "florent@wehost.fr" should managed 2 confirmed reservations between "2018-01-01" and "2018-12-31"
        
        And User "user@wehost.fr" should managed 0 reservations and 0 cleanings with 0 extracharges
        And User "user@wehost.fr" should managed 0 confirmed reservations between "2018-01-01" and "2018-12-31"
        
        And User "admin@wehost.fr" should managed 5 reservations and 3 cleanings with 3 extracharges
        And User "admin@wehost.fr" should managed 4 confirmed reservations between "2018-01-01" and "2018-12-31"