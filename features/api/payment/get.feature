@api
Feature: Get Payments
  In order to view all payments
  As a user
  I need to be able to list all payments

  Background:
    Given I have the following users:
      | username | password | roles            |
      | abc      | abc      | ROLE_SUPER_ADMIN |
    And I am authorised as "abc"

  @resetSchema
  @alice(Payment)
  Scenario: Get all payments
    When I send a JSON "GET" request to "/api/payments"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON response should contain:
    """
    {
      "@context": "/api/contexts/Payment",
      "@id": "/api/payments",
      "@type": "hydra:Collection",
      "hydra:member": [
          {
              "@id": "/api/payments/1",
              "@type": "Payment",
              "method": {
                  "name": "cash"
              },
              "status": "captured",
              "message": null,
              "completed": "2017-05-21T19:25:04+02:00"
          }
        ],
        "hydra:totalItems": 1
      }
    """

  @resetSchema
  @alice(Payment)
  Scenario: Get a single payment
    When I send a JSON "GET" request to "/api/payments/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON response should contain:
    """
    {
      "@context": "/api/contexts/Payment",
      "@id": "/api/payments/1",
      "@type": "Payment",
      "method": {
          "name": "cash"
      },
      "status": "captured",
      "message": null,
      "completed": "2017-05-21T19:25:04+00:00"
    }
    """