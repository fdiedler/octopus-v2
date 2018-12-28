Feature: Monthly invoicing

  Background:
    Given I have a market "Paris"
      And I have a host "Benoit" with email "benoit@test.com"
      And I have a property "LOFT-1" for host "Benoit" and market "Paris"
      And the property "LOFT-1" is being managed between "2017-01-01" and "2017-12-31"
      And I have a host "Romain" with email "romain@test.com"
      And I have a property "LOFT-2" for host "Romain" and market "Paris"
      And the property "LOFT-2" is being managed between "2017-01-01" and "2017-12-31"
  
  Scenario: Basic monthly invoices
    Given the property "LOFT-2" has a "confirmed" reservation "RES-1" between "2017-09-01" and "2017-09-05"
      And the reservation "RES-1" brought the host a profit of "€250" while guest paid a cleaning fee of "€20"
      And the reservation "RES-1" has a cleaning of "€33" schedulted the "2017-09-05"
      And the property "LOFT-2" has a "confirmed" reservation "RES-2" between "2017-09-08" and "2017-09-10"
      And the reservation "RES-2" brought the host a profit of "€140" while guest paid a cleaning fee of "€20"
      And the property "LOFT-2" has a cleaning of "€150" schedulted the "2017-09-30" entitled "Deep extra cleaning"
      And all the reservations pricing details were synced with Airbnb just now
     When I generate invoices for "September 2017"
     Then an invoice "2017-9-2" of "€241.33" and additional "€48.27" taxes should exist for property "LOFT-2" for "September 2017" with details:
          | Réservation 1 septembre : 20% de €230.00 ttc (versement reçu €250.00 - frais de ménage voyageur €20.00)  | €38.33 | €7.67 |
          | Réservation 1 septembre : Ménage + Blanchisserie + Linge de Maison (effectué le 5 septembre)             | €33.00 | €6.60 |
          | Réservation 8 septembre : 20% de €120.00 ttc (versement reçu €140.00 - frais de ménage voyageur €20.00)  | €20.00 | €4.00 |
          | Réservation 8 septembre -- pas de données ménage                                                         | €0.00  | €0.00 |
          | Standard du 30 septembre                                                                                 | €150.00| €30.00|
      And property "LOFT-1" should have no invoice for "September 2017"
      
  Scenario: I create monthly invoices
    Given the property "LOFT-2" has a "confirmed" reservation "RES-1" between "2017-09-01" and "2017-09-05"
      And the reservation "RES-1" brought the host a profit of "€250" while guest paid a cleaning fee of "€20"
      And the reservation "RES-1" has a cleaning of "€33" schedulted the "2017-09-05"
      And the property "LOFT-2" has a "confirmed" reservation "RES-2" between "2017-09-08" and "2017-09-10"
      And the reservation "RES-2" brought the host a profit of "€140" while guest paid a cleaning fee of "€20"
      And the reservation "RES-2" has a cleaning of "€33" schedulted the "2017-09-10"
      And the reservation "RES-2" has a cleaning of "€60" schedulted the "2017-09-11"
      And the property "LOFT-2" has a "confirmed" reservation "RES-3" between "2017-09-10" and "2017-09-12"
      And the reservation "RES-3" brought the host a profit of "€140" while guest paid a cleaning fee of "€20"
      And the reservation "RES-3" has a cleaning of "€33" schedulted the "2017-09-12"
      And the reservation "RES-3" has a discount of "€10"
      And the property "LOFT-2" has a "confirmed" reservation "RES-4" between "2017-09-13" and "2017-09-14"
      And the reservation "RES-4" brought the host a profit of "€110" while guest paid a cleaning fee of "€20"
      And the reservation "RES-4" has a cleaning of "€60" schedulted the "2017-09-12"
      And all the reservations pricing details were synced with Airbnb just now
     When I generate invoices for "September 2017"
     Then an invoice "2017-9-2" of "€495.66" and additional "€99.14" taxes should exist for property "LOFT-2" for "September 2017" with details:
          | Réservation 1 septembre : 20% de €230.00 ttc (versement reçu €250.00 - frais de ménage voyageur €20.00)  | €38.33 | €7.67 |
          | Réservation 1 septembre : Ménage + Blanchisserie + Linge de Maison (effectué le 5 septembre)             | €33.00 | €6.60 |
          | Réservation 8 septembre : 20% de €120.00 ttc (versement reçu €140.00 - frais de ménage voyageur €20.00)  | €20.00 | €4.00 |
          | Réservation 8 septembre : Ménage + Blanchisserie + Linge de Maison (effectué le 10 septembre)            | €33.00 | €6.60 |
          | Réservation 8 septembre : Ménage + Blanchisserie + Linge de Maison (effectué le 11 septembre)            | €60.00 | €12.00 |
          | Réservation 10 septembre : 20% de €110.00 ttc (versement reçu €140.00 - réduction €10.00 - frais de ménage voyageur €20.00)  | €18.33 | €3.67 |
          | Réservation 10 septembre : Ménage + Blanchisserie + Linge de Maison (effectué le 12 septembre)           | €33.00 | €6.60 |
          | Réservation 13 septembre : 20% de €90.00 ttc (versement reçu €110.00 - frais de ménage voyageur €20.00)  | €0.00 | €0.00 |
          | Réservation 13 septembre : Facturation minimum                                                           | €200.00 | €40.00 |
          | Réservation 13 septembre : Ménage + Blanchisserie + Linge de Maison (effectué le 12 septembre)           | €60.00 | €12.00 |
      And property "LOFT-1" should have no invoice for "September 2017"

  Scenario: Extra charge and commercial gesture
    Given the property "LOFT-2" has a "confirmed" reservation "RES-1" between "2017-09-01" and "2017-09-05"
    And the reservation "RES-1" brought the host a profit of "€250" while guest paid a cleaning fee of "€20"
    And the reservation "RES-1" has a cleaning of "€33" schedulted the "2017-09-05"
    And the property "LOFT-2" has a "confirmed" reservation "RES-2" between "2017-09-08" and "2017-09-10"
    And the reservation "RES-2" brought the host a profit of "€140" while guest paid a cleaning fee of "€20"
    And the reservation "RES-2" has a cleaning of "€33" schedulted the "2017-09-10"
    And the property "LOFT-2" has an extra charge of "€35" with "€7" taxes on "2017-09-09" for "Remplacement de chaise cassée"
    And the property "LOFT-2" has an extra charge of "-€40" with "-€10" taxes on "2017-09-30" for "Geste commercial"
    And all the reservations pricing details were synced with Airbnb just now
    When I generate invoices for "September 2017"
    Then an invoice "2017-9-2" of "€119.33" and additional "€21.87" taxes should exist for property "LOFT-2" for "September 2017" with details:
      | Réservation 1 septembre : 20% de €230.00 ttc (versement reçu €250.00 - frais de ménage voyageur €20.00)  | €38.33 | €7.67 |
      | Réservation 1 septembre : Ménage + Blanchisserie + Linge de Maison (effectué le 5 septembre)             | €33.00 | €6.60 |
      | Réservation 8 septembre : 20% de €120.00 ttc (versement reçu €140.00 - frais de ménage voyageur €20.00)  | €20.00 | €4.00 |
      | Réservation 8 septembre : Ménage + Blanchisserie + Linge de Maison (effectué le 10 septembre)            | €33.00 | €6.60 |
      | Remplacement de chaise cassée                                                                            | €35.00 | €7.00 |
      | Geste commercial                                                                                         | -€40.00 | -€10.00 |

  Scenario: Pricing details not collected yet
    Given the property "LOFT-2" has a "confirmed" reservation "RES-1" between "2017-09-01" and "2017-09-05"
    When I generate invoices for "September 2017"
    Then an invoice "2017-9-2" of "€0.00" and additional "€0.00" taxes should exist for property "LOFT-2" for "September 2017" with details:
      | Réservation 1 septembre -- pas de données prix  | €0.00 | €0.00 |

  Scenario: Extra charge is billed on the correct month
    Given the property "LOFT-1" has an extra charge of "€35" with "€7" taxes on "2017-09-09" for "Remplacement de chaise cassée"
     When I generate invoices for "September 2017"
      And I generate invoices for "October 2017"
     Then an invoice "2017-9-1" of "€35.00" and additional "€7.00" taxes should exist for property "LOFT-1" for "September 2017" with details:
       | Remplacement de chaise cassée  | €35.00 | €7.00 |
    And property "LOFT-1" should have no invoice for "October 2017"

  Scenario: Reservations are billed on the correct time period
    Given the property "LOFT-1" has a "confirmed" reservation "RES-1" between "2017-09-01" and "2017-10-02"
      And the property "LOFT-1" has a "confirmed" reservation "RES-2" between "2017-09-30" and "2010-10-01"
      And the property "LOFT-1" has a "confirmed" reservation "RES-3" between "2017-10-01" and "2017-10-02"
      And the property "LOFT-1" has a "canceled" reservation "RES-4" between "2017-09-14" and "2017-09-16"
      And the reservation "RES-1" brought the host a profit of "€500" while guest paid a cleaning fee of "€0"
      And the reservation "RES-1" has a cleaning of "€33" schedulted the "2017-09-01"
      And the reservation "RES-2" brought the host a profit of "€500" while guest paid a cleaning fee of "€0"
      And the reservation "RES-2" has a cleaning of "€33" schedulted the "2017-09-30"
      And the reservation "RES-3" brought the host a profit of "€500" while guest paid a cleaning fee of "€0"
      And the reservation "RES-3" has a cleaning of "€33" schedulted the "2017-10-01"
      And all the reservations pricing details were synced with Airbnb just now
    When I generate invoices for "September 2017"
    Then an invoice "2017-9-1" of "€232.66" and additional "€46.54" taxes should exist for property "LOFT-1" for "September 2017" with details:
      | Réservation 1 septembre : 20% de €500.00 ttc (versement reçu €500.00 - frais de ménage voyageur €0.00)  | €83.33  | €16.67 |
      | Réservation 1 septembre : Ménage + Blanchisserie + Linge de Maison (effectué le 1 septembre)            | €33.00 | €6.60 |
      | Réservation 30 septembre : 20% de €500.00 ttc (versement reçu €500.00 - frais de ménage voyageur €0.00) | €83.33  | €16.67 |
      | Réservation 30 septembre : Ménage + Blanchisserie + Linge de Maison (effectué le 30 septembre)          | €33.00 | €6.60 |
