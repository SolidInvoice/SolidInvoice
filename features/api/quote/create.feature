@api
Feature: Create Quotes
  In order to bill clients
  As a user
  I need to be able to create quotes

  Background:
    Given I have the following users:
    | username | password | roles            |
    | abc      | abc      | ROLE_SUPER_ADMIN |
    And I am authorised as "abc"

  @resetSchema
  @alice(Client)
  Scenario: Create a new quote
    When I send a JSON "POST" request to "/api/quotes" with body:
    """
    {
      "users" : [
          {"id" : 1}
      ],
      "client": "/api/clients/1",
      "discount": {
          "type": "percentage",
          "value": 10
      },
      "items" : [
          {
              "price": 100,
              "qty": 1,
              "description": "Foo Item"
          }
      ]
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON response should contain:
    """
    {
      "@context": "/api/contexts/Quote",
      "@id": "/api/quotes/1",
      "@type": "Quote",
      "id": 1,
      "status": "draft",
      "client": "/api/clients/1",
      "total": "$90.00",
      "baseTotal": "$100.00",
      "tax": "$0.00",
      "discount": {
          "type": "percentage",
          "value": 10
      },
      "terms": null,
      "notes": null,
      "due": null,
      "items": [
        {
          "id": 1,
          "description": "Foo Item",
          "price": "$100.00",
          "qty": 1,
          "tax": null,
          "total": "$100.00"
        }
      ],
      "users": [
        {
          "id": 1
        }
      ]
    }
    """
    And 1 quote should have been created