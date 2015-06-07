@installation
Feature: Install application
  In order to use the application
  As a user
  I need to be able to install the application

  Background:
    Given The application is not installed

  Scenario: Redirect to installation when application is not installed
    And I am on the homepage
    Then I should be on "/install/system_check"

  Scenario: Installation System Check
    Given I am on "/install/system_check"
    Then I should see "CSBill Installation - Requirements Check"
    And I should not see an ".alert-danger" element
    When I follow "continue_step"
    Then I should be on "/install/config"

  Scenario: Database Config
    Given I am on "/install/system_check"
    When I follow "continue_step"
    And I am on "/install/config"
    And I fill in select2 input "config_step_database_config_driver" with "pdo_mysql"
    And I fill in select2 input "config_step_email_settings_transport" with "mail"
    And I fill in the following:
      | User          | root       |
      | Database Name | csbill     |
    And I press "continue_step"
    Then The config should contain the following values:
      | database_driver   | pdo_mysql |
      | database_host     | localhost |
      | database_port     | 3306      |
      | database_name     | csbill    |
      | database_user     | root      |
      | database_password |           |
      | mailer_transport  | mail      |
      | installed         |           |

  Scenario: Installation Process Setup
    Given I am on "/install/system_check"
    When I follow "continue_step"
    And I am on "/install/config"
    And I fill in select2 input "config_step_database_config_driver" with "pdo_mysql"
    And I fill in select2 input "config_step_email_settings_transport" with "mail"
    And I fill in the following:
      | User          | root       |
      | Database Name | csbill     |
    And I press "continue_step"
    Then I should be on "/install/process"
    And I am on "/install/process?action=createdb"
    And I am on "/install/process?action=migrations"
    And I am on "/install/process?action=fixtures"
    And I am on "/install/process"
    And I follow "continue_step"
    Then I should be on "/install/setup"

  Scenario: System Setup
    Given I am on "/install/system_check"
    When I follow "continue_step"
    And I am on "/install/config"
    And I fill in select2 input "config_step_database_config_driver" with "pdo_mysql"
    And I fill in select2 input "config_step_email_settings_transport" with "mail"
    And I fill in the following:
      | User          | root       |
      | Database Name | csbill     |
    And I press "continue_step"
    And I am on "/install/process?action=createdb"
    And I am on "/install/process?action=migrations"
    And I am on "/install/process?action=fixtures"
    And I am on "/install/process"
    And I follow "continue_step"
    Then I should be on "/install/setup"
    And I fill in select2 input "system_information_locale" with "en_US"
    And I fill in select2 input "system_information_currency" with "USD"
    And I fill in the following:
      | Username        | admin       |
      | Email address   | foo@bar.com |
      | Password        | foobar      |
      | Repeat Password | foobar      |
    And I press "continue_step"
    Then I should be on "/install/finish"
    And I should see "You have successfully installed CSBill!"
    And The config should contain the following values:
      | currency  | USD   |
      | locale    | en_US |
    And The config value "installed" should not be empty
    And the following user must exist:
      | username | email       | password |
      | admin    | foo@bar.com | foobar   |