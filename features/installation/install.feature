@installation
Feature: Install application
  In order to use the application
  As a user
  I need to be able to install the application

  Background:
    Given The application is not installed

  Scenario: Redirect to installation when application is not installed
    Given I am on the homepage
    Then I should be on "/install/system_check"

  Scenario: Installation
    Given I am on "/install/system_check"
    Then I should see "CSBill Installation - Requirements Check"
    And I should not see an ".alert-danger" element
    When I follow "continue_step"
    Then I should be on "/install/config"
    And I fill in select2 input "Driver" with "mysql"
    And I fill in select2 input "Transport" with "Sendmail"
    And I fill in the following:
      | User          | root        |
      | Database Name | csbill_test |
    And I press "continue_step"
    Then The config should contain the following values:
      | database_driver   | pdo_mysql   |
      | database_host     | localhost   |
      | database_port     | 3306        |
      | database_name     | csbill_test |
      | database_user     | root        |
      | database_password |             |
      | mailer_transport  | sendmail    |
      | installed         |             |
    And I should be on "/install/process"
    When I wait for "continue_step" to become available
    And I follow "continue_step"
    Then I should be on "/install/setup"
    And I fill in select2 input "Locale" with "English"
    And I fill in select2 input "Currency" with "US Dollar"
    And I fill in the following:
      | Username        | admin       |
      | Email address   | foo@bar.com |
      | Password        | foobar      |
      | Repeat Password | foobar      |
    And I press "continue_step"
    Then I should be on "/install/finish"
    And I should see "You have successfully installed CSBill!"
    And The config should contain the following values:
      | currency | USD |
      | locale   | en  |
    And The config value "installed" should not be empty
    And the following user must exist:
      | username | email       | password |
      | admin    | foo@bar.com | foobar   |