@api
Feature: Get Quotes
  In order to view all quotes
  As a user
  I need to be able to list all quotes

  Background:
    Given I have the following users:
      | username | password | roles            |
      | abc      | abc      | ROLE_SUPER_ADMIN |
    And I am authorised as "abc"

  @resetSchema
  @alice(Quote)
  Scenario: Get all quotes
    When I send a JSON "GET" request to "/api/quotes"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON response should contain:
    """
    {
      "@context": "/api/contexts/Quote",
      "@id": "/api/quotes",
      "@type": "hydra:Collection",
      "hydra:member": [
          {
            "id": 1,
            "status": "draft",
            "client": "/api/clients/1",
            "total": "$100.00",
            "baseTotal": "$100.00",
            "tax": "$0.00",
            "discount": {
                "type": null,
                "value": null
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
              "/api/contacts/1"
            ]
          }
          ],
        "hydra:totalItems": 1
      }
    """

  @resetSchema
  @alice(Quote)
  Scenario: Get a quote
    When I send a JSON "GET" request to "/api/quotes/1"
    Then the response status code should be 200
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
      "total": "$100.00",
      "baseTotal": "$100.00",
      "tax": "$0.00",
      "discount": {
          "type": null,
          "value": null
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
        "/api/contacts/1"
      ]
    }
    """