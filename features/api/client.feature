@api
Feature: Manage Clients
  In order to bill clients
  As a user
  I need to be able manage clients

  Scenario: Create a client
    When I send a "POST" request to "/api/clients" with body:
    """
    {
      "name": "Dummy User",
      "website": "https://google.com",
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
        "website": "https://google.com",
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