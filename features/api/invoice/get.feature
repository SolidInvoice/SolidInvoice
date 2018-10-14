@api
Feature: Get Invoices
  In order to view all invoices
  As a user
  I need to be able to list all invoices

  Background:
    Given I have the following users:
      | username | password | roles            |
      | abc      | abc      | ROLE_SUPER_ADMIN |
    And I am authorised as "abc"

  @resetSchema
  @alice(Invoice)
  Scenario: Get all invoices
    When I send a JSON "GET" request to "/api/invoices"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON response should contain:
    """
    {
      "@context": "/api/contexts/Invoice",
      "@id": "/api/invoices",
      "@type": "hydra:Collection",
      "hydra:member": [
          {
            "id": 1,
            "status": "draft",
            "client": "/api/clients/1",
            "total": "$100.00",
            "baseTotal": "$100.00",
            "balance": "$100.00",
            "tax": "$0.00",
            "discount": {
                "type": null,
                "value": null
            },
            "terms": null,
            "notes": null,
            "due": null,
            "paidDate": null,
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
            ],
            "recurring": false
          }
          ],
        "hydra:totalItems": 1
      }
    """

  @resetSchema
  @alice(Invoice)
  Scenario: Get an invoice
    When I send a JSON "GET" request to "/api/invoices/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON response should contain:
    """
    {
      "@context": "/api/contexts/Invoice",
      "@id": "/api/invoices/1",
      "@type": "Invoice",
      "id": 1,
      "status": "draft",
      "client": "/api/clients/1",
      "total": "$100.00",
      "baseTotal": "$100.00",
      "balance": "$100.00",
      "tax": "$0.00",
      "discount": {
          "type": null,
          "value": null
      },
      "terms": null,
      "notes": null,
      "due": null,
      "paidDate": null,
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
      ],
      "recurring": false
    }
    """