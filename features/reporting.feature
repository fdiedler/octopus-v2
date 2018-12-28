Feature: Financial Reporting

  Background:
    Given I have a market "Paris"
      And I have a market "Bordeaux"
      And I have a user "victor@wehost.fr"
      And user "victor@wehost.fr" is the manager of "Paris" with a percentage of 15 percent

      And I have a host "Benoit" with email "benoit@test.com"
      And I have a property "LOFT-1" for host "Benoit" and market "Paris"
      And the property "LOFT-1" is being managed between "2017-01-01" and "2017-12-31"
      And the contract for "LOFT-1" has a 25 percent commission with a cleaning fee of "€20" without taxes
      And the property "LOFT-1" has a "confirmed" reservation "RES-1" between "2017-09-01" and "2017-09-05"
      And the reservation "RES-1" brought the host a profit of "€250" while guest paid a cleaning fee of "€20"
      And the reservation "RES-1" has a cleaning of "€20" schedulted the "2017-09-05"
      And the property "LOFT-1" has a "confirmed" reservation "RES-2" between "2017-09-08" and "2017-09-10"
      And the reservation "RES-2" has a cleaning of "€20" schedulted the "2017-09-10"
      And the reservation "RES-2" brought the host a profit of "€140" while guest paid a cleaning fee of "€30"
      And the property "LOFT-1" has an extra charge of "-€20" with "-€4" taxes on "2017-09-30" for "Commercial gesture"

      And I have a host "Romain" with email "romain@test.com"
      And I have a property "LOFT-2" for host "Romain" and market "Bordeaux"
      And the property "LOFT-2" is being managed between "2017-01-01" and "2017-12-31"
      And the contract for "LOFT-2" has a 20 percent commission with a cleaning fee of "€33" without taxes
      And the property "LOFT-2" has a "confirmed" reservation "RES-3" between "2017-09-01" and "2017-09-05"
      And the reservation "RES-3" brought the host a profit of "€422" while guest paid a cleaning fee of "€50"
      And the reservation "RES-3" has a cleaning of "€33" schedulted the "2017-09-12"
      And the property "LOFT-2" has an extra charge of "€50" with "€10" taxes on "2017-09-30" for "Some extra charge"
      And the property "LOFT-2" has a cleaning of "€150" schedulted the "2017-09-14" entitled "Deep extra cleaning"
      
      And the property "LOFT-2" has a "confirmed" reservation "RES-4" between "2017-09-30" and "2017-10-02"
      And the reservation "RES-4" brought the host a profit of "€110" while guest paid a cleaning fee of "€20"
      
      And the property "LOFT-2" has a "pending" reservation "RES-4" between "2017-09-12" and "2017-09-14"
      And the property "LOFT-2" has a "canceled" reservation "RES-5" between "2017-09-14" and "2017-09-16"
      And the reservation "RES-5" brought the host a profit of "€0" while guest paid a cleaning fee of "€0"
        
      And all the reservations pricing details were synced with Airbnb just now

  Scenario: Computation of the turnover for a given period/market
    Then the turnover between "2017-09-01" and "2017-09-30" for market "Paris" is "€90.84" with "€18.16" taxes
     And the turnover between "2017-09-01" and "2017-09-30" for market "Bordeaux" is "€495.00" with "€99" taxes

  Scenario: Computation of the total cleaning costs for a given period/market
    Then the total cleaning costs between "2017-09-01" and "2017-09-30" for market "Paris" are "€40" with "€8" taxes
     And the total cleaning costs between "2017-09-01" and "2017-09-30" for market "Bordeaux" are "€183" with "€36.60" taxes

  Scenario: Computation of the number of billable hosts
    Then the number of billable hosts between "2017-09-01" and "2017-09-30" for market "Paris" is 1
     And the number of billable hosts between "2017-09-01" and "2017-09-30" for market "Bordeaux" is 1

  Scenario: Computation of the commission of a manager for a given period/market
    Then the commission of the manager of "Paris" between "2017-09-01" and "2017-09-30" is "€51"
     And the commission of the manager of "Bordeaux" between "2017-09-01" and "2017-09-30" is "€0"
