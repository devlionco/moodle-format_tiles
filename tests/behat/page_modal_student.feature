@format @format_tiles @format_tiles_mod_modal  @format_tiles_page_modal_student @javascript
Feature: Student can open a page

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections | enablecompletion |
      | Course 2 | C2        | tiles  | 0             | 5           | 1                |
    And the following "activities" exist:
      | activity | name           | intro                 | course | idnumber | section | visible |
      | quiz     | Test quiz name | Test quiz description | C2     | quiz1    | 3       | 1       |
      | page     | Test page name | Test page description | C2     | page1    | 3       | 1       |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C2     | student |
    And the following config values are set as admin:
      | config                 | value    | plugin       |
      | enablecompletion       | 1        | core         |
      | modalmodules           | page     | format_tiles |
      | modalresources         | pdf,html | format_tiles |
      | assumedatastoreconsent | 1        | format_tiles |
      | reopenlastsection      | 0        | format_tiles |
      | usejavascriptnav       | 1        | format_tiles |
      | jsmaxstoreditems       | 0        | format_tiles |
    # We set jsmaxstoreditems to zero as otherwise when we switch between subtiles and tiles format we may not see an immediate change in display

  @javascript
  Scenario: Open page using modal as student with subtiles on
    When format_tiles subtiles are on for course "Course 2"
    And I log in as "student1"
    And I am on "Course 2" course homepage
    And I click on tile "3"
    And I wait until the page is ready
    And I click format tiles activity "Test page name"
    And I wait until the page is ready
    And "Test page name" "dialogue" should be visible
    And "Test page content" "text" should be visible
    And "Close" "button" should exist in the "Test page name" "dialogue"
    And I click on "Close" "button"
    And I wait until the page is ready

    And I click on close button for tile "3"
    And I wait "1" seconds
    And "Test page content" "text" should not be visible
    And I log out

  @javascript
  Scenario: Open page using modal as student - with subtiles off
    When format_tiles subtiles are off for course "Course 2"
    And I log in as "student1"
    And I am on "Course 2" course homepage
    And I click on tile "3"
    And I wait until the page is ready
    And I click format tiles activity "Test page name"
    And I wait until the page is ready
    And "Test page name" "dialogue" should be visible
    And "Test page content" "text" should be visible
    And "Close" "button" should exist in the "Test page name" "dialogue"
    And I click on "Close" "button"
    And I wait until the page is ready

    And I click on close button for tile "3"
    And I wait "1" seconds
    And "Test page content" "text" should not be visible
    And I log out