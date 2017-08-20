@api @client
Feature: Edit Clients
  In order to have up to date information
  As a user
  I need to be able to edit clients

  Background:
    Given I have the following users:
      | username | password | roles            |
      | abc      | abc      | ROLE_SUPER_ADMIN |
    And I am authorised as "abc"

  @resetSchema
  @alice(Client)
  Scenario: Edit default client details
    When I send a JSON "PUT" request to "/api/clients/1" with body:
    """
    {
      "name": "Second User"
    }
    """
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
        "name": "Second User",
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