@api
Feature: Manage Clients
  In order to invoice clients
  As a user
  I need to be able to create clients

  Background:
    Given I have the following users:
    | username | password | roles            |
    | abc      | abc      | ROLE_SUPER_ADMIN |
    And I am authorised as "abc"

  @resetSchema
  Scenario: Create a client with the bare minimum information
    When I send a JSON "POST" request to "/api/clients" with body:
    """
    {
      "name": "Dummy User",
      "contacts": [
          {
              "firstName": "foo bar",
              "email": "foo@bar.com"
          }
      ]
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
        "@context": "/api/contexts/Client",
        "@id": "/api/clients/1",
        "@type": "https://schema.org/Corporation",
        "id": 1,
        "name": "Dummy User",
        "website": null,
        "status": "active",
        "currency": null,
        "contacts": [
            {
                "@id": "/api/contacts/1",
                "@type": "https://schema.org/Person",
                "id": 1,
                "firstName": "foo bar",
                "lastName": null,
                "email": "foo@bar.com",
                "additionalContactDetails": []
            }
        ],
        "addresses": [],
        "credit": "$0.00"
    }
    """
    And 1 client should have been created

  @resetSchema
  Scenario: Create a client with all the possible values
    When I send a JSON "POST" request to "/api/clients" with body:
    """
    {
      "name": "Second Dummy User",
      "website": "https://google.com",
      "currency": "USD",
      "contacts": [
          {
              "firstName": "foo",
              "lastName": "bar",
              "email": "foo@bar.com",
              "additionalContactDetails": [
                  {
                      "value": "foobar",
                      "type": "address"
                  }
              ]
          }
      ]
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
        "@context": "/api/contexts/Client",
        "@id": "/api/clients/1",
        "@type": "https://schema.org/Corporation",
        "id": 1,
        "name": "Second Dummy User",
        "website": "https://google.com",
        "status": "active",
        "currency": "USD",
        "contacts": [
            {
                "@id": "/api/contacts/1",
                "@type": "https://schema.org/Person",
                "id": 1,
                "firstName": "foo",
                "lastName": "bar",
                "email": "foo@bar.com",
                "additionalContactDetails": [
                  {
                    "type": "address",
                    "value": "foobar"
                  }
              ]
            }
        ],
        "addresses": [],
        "credit": "$0.00"
    }
    """
    And 1 client should have been created
