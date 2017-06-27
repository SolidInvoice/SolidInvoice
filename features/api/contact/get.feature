@api @client
Feature: Get Contact
  In order to view a contact
  As a user
  I need to be able to get the contact

  Background:
    Given I have the following users:
      | username | password | roles            |
      | abc      | abc      | ROLE_SUPER_ADMIN |
    And I am authorised as "abc"

  @resetSchema
  @alice(Client)
  Scenario: Fetches all clients
    When I send a JSON "GET" request to "/api/contacts/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
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
    }
    """