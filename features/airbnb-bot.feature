Feature: Interface with Airbnb

  Background:
    Given I have a market "Paris"
      And I have a host "Benoit" with email "benoit@test.com"
      And I have a property "LOFT-1" for host "Benoit" and market "Paris"
      And property "LOFT-1" corresponds to Airbnb ID "123456"
      And property "LOFT-1" has Airbnb Calendar url "https://www.airbnb.com/calendar/ical/16267639.ics?s=abcdefghijklmnopqrstuvwxyzabcdef"
      And a "GET" call to "https://www.airbnb.com/manage-your-space/123456/availability" returns a "200" with body from "airbnb/manage-your-space-request.html"

  Scenario: I sync all the reservations of a property
     Given a "GET" call to "https://www.airbnb.com/calendar/ical/16267639.ics?s=abcdefghijklmnopqrstuvwxyzabcdef" returns a "200" with body from "airbnb/sample.ics"
       And a "GET" call to "https://www.airbnb.com/reservation/itinerary?code=ABCDEF" returns a "200" with body from "airbnb/reservation-itinerary.html"
     When I sync the property "LOFT-1"
     Then the property "LOFT-1" should have 1 reservations
      And the reservation "1" should exist for "LOFT-1" with status "confirmed", checkin on "2016-12-29" and checkout on "2017-01-05"
      And the reservation "1" should have a guest named "Benoit Del Basso" with phone number "+33 X XX XX XX XX"
      #And the reservation "1" should bring the host a profit of "€1735.20" while guest pays a cleaning fee of "€30"

  Scenario: A reservation that was canceled is synchronized correctly
    Given the property "LOFT-1" has a "confirmed" reservation "RES-1" between "2016-12-29" and "2016-01-05"
      And the property "LOFT-1" has a "confirmed" reservation "RES-2" between "2016-01-05" and "2016-01-07"
      And the reservation "RES-1" corresponds to Airbnb ID "ABCDEF"
      And the reservation "RES-2" corresponds to Airbnb ID "AAAAAA"
      And a "GET" call to "https://www.airbnb.com/calendar/ical/16267639.ics?s=abcdefghijklmnopqrstuvwxyzabcdef" returns a "200" with body from "airbnb/sample.ics"
      And a "GET" call to "https://www.airbnb.com/reservation/itinerary?code=ABCDEF" returns a "200" with body from "airbnb/reservation-itinerary.html"
      And a "GET" call to "https://www.airbnb.com/reservation/itinerary?code=AAAAAA" returns a "200" with body from "airbnb/reservation-itinerary.html"
     When I sync the property "LOFT-1"
     Then the reservation "RES-1" should exist for "LOFT-1" with status "confirmed", checkin on "2016-12-29" and checkout on "2016-01-05"
      And the reservation "RES-2" should exist for "LOFT-1" with status "canceled", checkin on "2016-01-05" and checkout on "2016-01-07"

  Scenario: A reservation that was canceled and which code is not valid any more is synchronized correctly
    Given the property "LOFT-1" has a "confirmed" reservation "RES-1" between "2016-12-29" and "2016-01-05"
    And the property "LOFT-1" has a "confirmed" reservation "RES-2" between "2016-01-05" and "2016-01-07"
    And the reservation "RES-1" corresponds to Airbnb ID "ABCDEF"
    And the reservation "RES-2" corresponds to Airbnb ID "AAAAAA"
    And a "GET" call to "https://www.airbnb.com/calendar/ical/16267639.ics?s=abcdefghijklmnopqrstuvwxyzabcdef" returns a "200" with body from "airbnb/sample.ics"
    And a "GET" call to "https://www.airbnb.com/reservation/itinerary?code=ABCDEF" returns a "200" with body from "airbnb/reservation-itinerary.html"
    And a "GET" call to "https://www.airbnb.com/reservation/itinerary?code=AAAAAA" returns a "304" redirection to "https://www.airbnb.com/hosting"
    When I sync the property "LOFT-1"
    Then the reservation "RES-1" should exist for "LOFT-1" with status "confirmed", checkin on "2016-12-29" and checkout on "2016-01-05"
    And the reservation "RES-2" should exist for "LOFT-1" with status "canceled", checkin on "2016-01-05" and checkout on "2016-01-07"

  Scenario: A reservation that is still pending is synchronized correctly
    Given a "GET" call to "https://www.airbnb.com/calendar/ical/16267639.ics?s=abcdefghijklmnopqrstuvwxyzabcdef" returns a "200" with body from "airbnb/pending.ics"
    And a "GET" call to "https://www.airbnb.com/reservation/itinerary?code=ABCDEF" returns a "304" redirection to "https://www.airbnb.com/z/q/16267639?for_reservation_code=abcdef"
    When I sync the property "LOFT-1"
    Then the property "LOFT-1" should have 1 reservations
    Then the reservation "1" should exist for "LOFT-1" with status "pending", checkin on "2016-12-29" and checkout on "2017-01-05"

  Scenario: A reservation that is pending then confirmed is synchronized correctly
    Given the property "LOFT-1" has a "pending" reservation "1" between "2016-12-29" and "2017-01-04"
    And the reservation "1" corresponds to Airbnb ID "ABCDEF"
    And a "GET" call to "https://www.airbnb.com/calendar/ical/16267639.ics?s=abcdefghijklmnopqrstuvwxyzabcdef" returns a "200" with body from "airbnb/sample.ics"
    And a "GET" call to "https://www.airbnb.com/reservation/itinerary?code=ABCDEF" returns a "200" with body from "airbnb/reservation-itinerary.html"
    When I sync the property "LOFT-1"
    Then the property "LOFT-1" should have 1 reservations
    And the reservation "1" should exist for "LOFT-1" with status "confirmed", checkin on "2016-12-29" and checkout on "2017-01-05"
    #And the Airbnb last pricing sync date for reservation "1" is not null

  Scenario: Synchronization status and details are filled correctly for each property
    Given a "GET" call to "https://www.airbnb.com/calendar/ical/16267639.ics?s=abcdefghijklmnopqrstuvwxyzabcdef" returns a "200" with body from "airbnb/sample.ics"
     And a "GET" call to "https://www.airbnb.com/reservation/itinerary?code=ABCDEF" returns a "200" with body from "airbnb/reservation-itinerary.html"
    When I sync the property "LOFT-1"
    Then the Airbnb last successful sync date for "LOFT-1" is not null
     And the Airbnb sync log for "LOFT-1" is not empty

  #Scenario: Reservation pricing details are set correctly
  #  Given a "GET" call to "https://www.airbnb.com/calendar/ical/16267639.ics?s=abcdefghijklmnopqrstuvwxyzabcdef" returns a "200" with body from "airbnb/sample.ics"
  #  And a "GET" call to "https://www.airbnb.com/reservation/itinerary?code=ABCDEF" returns a "200" with body from "airbnb/reservation-itinerary.html"
  #  When I sync the property "LOFT-1"
  #  Then the reservation "1" should bring the host a profit of "€1735.20" while guest pays a cleaning fee of "€30"
  #   And the Airbnb last pricing sync date for reservation "1" is not null

  #Scenario: Guest cleaning fee is set to a minimum
  #  Given a "GET" call to "https://www.airbnb.com/calendar/ical/16267639.ics?s=abcdefghijklmnopqrstuvwxyzabcdef" returns a "200" with body from "airbnb/sample.ics"
  #  And a "GET" call to "https://www.airbnb.com/reservation/itinerary?code=ABCDEF" returns a "200" with body from "airbnb/reservation-itinerary-no-cleaning-fee.html"
  #  And the property "LOFT-1" has a default guest cleaning fee of "€20"
  #  When I sync the property "LOFT-1"
  #  Then the reservation "1" should bring the host a profit of "€1735.20" while guest pays a cleaning fee of "€20"
  #   And the Airbnb last pricing sync date for reservation "1" is not null
