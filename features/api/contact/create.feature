@api @contact
Feature: Add Contacts
  In order to add more contacts to a client
  As a user
  I need to be able to create contacts

  Background:
    Given I have the following users:
    | username | password | roles            |
    | abc      | abc      | ROLE_SUPER_ADMIN |
    And I am authorised as "abc"

  @resetSchema
  @alice(Client)
  Scenario: Add an additional contact to a client
    When I send a JSON "POST" request to "/api/contacts" with body:
    """
    {
        "client": "/api/clients/1",
        "firstName": "foo bar",
        "email": "foo@bar.com"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
        "@context": "/api/contexts/Contact",
        "@id": "/api/contacts/3",
        "@type": "https://schema.org/Person",
        "id": 3,
        "firstName": "foo bar",
        "lastName": null,
        "client": "/api/clients/1",
        "email": "foo@bar.com",
        "additionalContactDetails": []
    }
    """
    And 1 contact should have been created

  @resetSchema
  @alice(Client)
  Scenario: Add a contact with additional details
    When I send a JSON "POST" request to "/api/contacts" with body:
    """
    {
        "client": "/api/clients/1",
        "firstName": "foo bar",
        "email": "foo@bar.com",
        "additionalContactDetails": [
            {
                "type": "address",
                "value": "foobar"
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
        "@context": "/api/contexts/Contact",
        "@id": "/api/contacts/3",
        "@type": "https://schema.org/Person",
        "id": 3,
        "firstName": "foo bar",
        "lastName": null,
        "client": "/api/clients/1",
        "email": "foo@bar.com",
        "additionalContactDetails": [
            {
                "type": "address",
                "value": "foobar"
            }
        ]
    }
    """
    #And 1 contact should have been created

  @resetSchema
  @alice(Client)
  Scenario: Can't add a contact without a client
    When I send a JSON "POST" request to "/api/contacts" with body:
    """
    {
        "firstName": "foo bar",
        "email": "foo@bar.com"
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
        "@context": "/api/contexts\/ConstraintViolationList",
        "@type": "ConstraintViolationList",
        "hydra:title": "An error occurred",
        "hydra:description": "client: This value should not be blank.",
        "violations": [
            {
                "propertyPath": "client",
                "message": "This value should not be blank."
            }
        ]
    }
    """
