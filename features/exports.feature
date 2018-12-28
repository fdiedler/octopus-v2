Feature: Exports

  Background:
    Given I have a market "Paris"
      And I have a host "Benoit" with email "benoit@test.com"
      And I have a property "LOFT-1" for host "Benoit" and market "Paris"
      And the property "LOFT-1" is being managed between "2017-01-01" and "2017-12-31"
      And the contract for "LOFT-1" has a 25 percent commission with a cleaning fee of "€20" without taxes
      And the property "LOFT-1" has a "confirmed" reservation "RES-1" between "2017-09-01" and "2017-09-05"
      And the reservation "RES-1" brought the host a profit of "€250" while guest paid a cleaning fee of "€20"

  Scenario: Full Reservations Export
    Then I can export all the reservations
