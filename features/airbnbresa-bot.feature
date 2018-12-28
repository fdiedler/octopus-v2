Feature: Interface with Airbnb princing bot

  Background:
    Given I have a market "Paris"
      And I have a host "Benoit" with email "benoit@test.com"
      And I have a property "LOFT-1" for host "Benoit" and market "Paris"
      And property "LOFT-1" corresponds to Airbnb ID "123456"
      And the property "LOFT-1" has a default guest cleaning fee of "€20"
      And the property "LOFT-1" has a "confirmed" reservation "RES-1" between "2016-12-29" and "2016-01-05"
      And the reservation "RES-1" corresponds to Airbnb ID "ABCDEF"
      And the reservation "RES-1" brought the host a profit of "€0" while guest paid a cleaning fee of "€20"
      
  Scenario: The host profil of a single reservation was sync correctly (email contains only one reservation)
      When I sync the reservation price "RES-1"
      Then the reservation "RES-1" should bring the host a profit of "€1234.25" while guest pays a cleaning fee of "€20"
        And the reservation "RES-1" should not have any discount
      
  Scenario: The host profil of a single reservation was sync correctly (email contains several reservations)
      Given the property "LOFT-1" has a "confirmed" reservation "RES-2" between "2018-08-05" and "2018-08-11"
        And the reservation "RES-2" corresponds to Airbnb ID "HMC8XYMRMH"
        And the reservation "RES-2" brought the host a profit of "€0" while guest paid a cleaning fee of "€20"
      When I sync the reservation price "RES-2"
      Then the reservation "RES-2" should bring the host a profit of "€703.72" while guest pays a cleaning fee of "€20"
        And the reservation "RES-2" should not have any discount
     
  Scenario: The host profil of a single reservation was sync correctly (email contains only one reservation with a discount)
      Given the property "LOFT-1" has a "confirmed" reservation "RES-3" between "2018-08-24" and "2018-08-27"
        And the reservation "RES-3" corresponds to Airbnb ID "HM49B3XKR8"
        And the reservation "RES-3" brought the host a profit of "€0" while guest paid a cleaning fee of "€20"
      When I sync the reservation price "RES-3"
      Then the reservation "RES-3" should bring the host a profit of "€208.10" while guest pays a cleaning fee of "€20"
        And the reservation "RES-3" should bring a discount of "€72"
        
  Scenario: The host profil of a single reservation was sync correctly (email contains several reservations with several discounts)
      Given the property "LOFT-1" has a "confirmed" reservation "RES-4" between "2018-08-07" and "2018-08-12"
        And the reservation "RES-4" corresponds to Airbnb ID "HMEANK2TME"
        And the reservation "RES-4" brought the host a profit of "€0" while guest paid a cleaning fee of "€20"
      When I sync the reservation price "RES-4"
      Then the reservation "RES-4" should bring the host a profit of "€0" while guest pays a cleaning fee of "€20"
        And the reservation "RES-4" should not have any discount