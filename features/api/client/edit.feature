@api
Feature: Edit Clients
  In order to have up to date information
  As a user
  I need to be able to edit clients

  @alice(Client)
  Scenario: Edit default client details
    When I send a "PUT" request to "/api/clients/1" with body:
    """
    {
      "name": "Second User"
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON nodes should contain:
      | name | Second User |