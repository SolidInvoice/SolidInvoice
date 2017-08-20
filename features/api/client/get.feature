@api @client
Feature: Get Clients
  In order to view clients
  As a user
  I need to be able to get clients

  Background:
    Given I have the following users:
      | username | password | roles            |
      | abc      | abc      | ROLE_SUPER_ADMIN |
    And I am authorised as "abc"

  @resetSchema
  @alice(Client)
  Scenario: Fetches all clients
    When I send a JSON "GET" request to "/api/clients"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
        "@context": "/api/contexts/Client",
        "@id": "/api/clients",
        "@type": "hydra:Collection",
        "hydra:member": [
            {
                "@id": "/api/clients/1",
                "@type": "https://schema.org/Corporation",
                "id": 1,
                "name": "Client One",
                "website": null,
                "status": "active",
                "currency": "USD",
                "vatNumber": null,
                "contacts": [
                    {
                        "@id": "/api/contacts/1",
                        "@type": "https://schema.org/Person",
                        "id": 1,
                        "firstName": "One",
                        "lastName": "One",
                        "email": "one@one.com",
                        "additionalContactDetails": [
                          {
                              "type": "cellphone",
                              "value": "1234567890"
                          }
                        ]
                    },
                    {
                        "@id": "/api/contacts/2",
                        "@type": "https://schema.org/Person",
                        "id": 2,
                        "firstName": "Two",
                        "lastName": null,
                        "email": "two@two.com",
                        "additionalContactDetails": []
                    }
                ],
                "addresses": [],
                "credit": "$0.00"
            }
        ],
        "hydra:totalItems": 1
    }
    """

  @resetSchema
  @alice(Client)
  Scenario: Fetch a specific client
    When I send a JSON "GET" request to "/api/clients/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
        "@context": "/api/contexts/Client",
        "@id": "/api/clients/1",
        "@type": "https://schema.org/Corporation",
        "id": 1,
        "name": "Client One",
        "website": null,
        "status": "active",
        "currency": "USD",
        "vatNumber": null,
        "contacts": [
            {
                "@id": "/api/contacts/1",
                "@type": "https://schema.org/Person",
                "id": 1,
                "firstName": "One",
                "lastName": "One",
                "email": "one@one.com",
                "additionalContactDetails": [
                  {
                      "type": "cellphone",
                      "value": "1234567890"
                  }
                ]
            },
            {
                "@id": "/api/contacts/2",
                "@type": "https://schema.org/Person",
                "id": 2,
                "firstName": "Two",
                "lastName": null,
                "email": "two@two.com",
                "additionalContactDetails": []
            }
        ],
        "addresses": [],
        "credit": "$0.00"
    }
    """