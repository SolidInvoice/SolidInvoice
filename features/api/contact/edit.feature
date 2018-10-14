@api @contact
Feature: Edit Contacts
  In order to have up to date information
  As a user
  I need to be able to edit contacts

  Background:
    Given I have the following users:
      | username | password | roles            |
      | abc      | abc      | ROLE_SUPER_ADMIN |
    And I am authorised as "abc"

  @resetSchema
  @alice(Client)
  Scenario: Edit contact details
    When I send a JSON "PUT" request to "/api/contacts/1" with body:
    """
    {
        "firstName": "Second",
        "additionalContactDetails": [
            {
                "type": "address",
                "value": "foobarbaz"
            }
        ]
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
        "@context": "/api/contexts/Contact",
        "@id": "/api/contacts/1",
        "@type": "https://schema.org/Person",
        "id": 1,
        "firstName": "Second",
        "lastName": "One",
        "client": "/api/clients/1",
        "email": "one@one.com",
        "additionalContactDetails": [
            {
                "type": "cellphone",
                "value": "1234567890"
            },
            {
                "type": "address",
                "value": "foobarbaz"
            }
        ]
    }
    """