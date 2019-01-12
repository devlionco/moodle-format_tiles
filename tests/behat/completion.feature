@format @format_tiles @format_tiles_completion @javascript
Feature: Teacher can add a page to a course and open it with subtiles off

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections | enablecompletion |
      | Course 1 | C1        | tiles  | 0             | 5           | 1                |
    And the following "activities" exist:
    # completion 1 is manual completion, 2 is automatic then add an extra column completionpass = 1
      | activity | name         | intro           | course | idnumber | section | visible | completion |
      | page     | Test page 1  | Test page intro | C1     | page1    | 1       | 1       | 1          |
      | page     | Test page 2  | Test page intro | C1     | page2    | 1       | 1       | 1          |
      | page     | Test page 3  | Test page intro | C1     | page3    | 2       | 1       | 1          |
      | page     | Test page 4  | Test page intro | C1     | page4    | 2       | 1       | 1          |
      | page     | Test page 5  | Test page intro | C1     | page5    | 2       | 1       | 1          |
      | page     | Test page 6  | Test page intro | C1     | page6    | 2       | 1       | 1          |
      | page     | Test page 7  | Test page intro | C1     | page7    | 3       | 1       | 1          |
      | page     | Test page 8  | Test page intro | C1     | page8    | 3       | 1       | 1          |
      | page     | Test page 9  | Test page intro | C1     | page9    | 3       | 1       | 1          |
      | page     | Test page 10 | Test page intro | C1     | page10   | 3       | 1       | 1          |
      | page     | Test page 11 | Test page intro | C1     | page11   | 3       | 1       | 1          |
      | page     | Test page 12 | Test page intro | C1     | page12   | 4       | 1       | 1          |
      | page     | Test page 13 | Test page intro | C1     | page13   | 4       | 1       | 1          |
      | page     | Test page 14 | Test page intro | C1     | page14   | 5       | 1       | 1          |
      | page     | Test page 15 | Test page intro | C1     | page15   | 5       | 1       | 1          |
      | page     | Test page 16 | Test page intro | C1     | page16   | 6       | 1       | 1          |
      | page     | Test page 17 | Test page intro | C1     | page17   | 6       | 1       | 1          |
      | page     | Test page 18 | Test page intro | C1     | page18   | 7       | 1       | 1          |
      | page     | Test page 19 | Test page intro | C1     | page19   | 7       | 1       | 1          |
      | page     | Test page 20 | Test page intro | C1     | page20   | 7       | 1       | 1          |

    #Activity counts for each tile based on above
    #    Tile    | Activity count
    #    Tile 1  | 2
    #    Tile 2  | 4
    #    Tile 3  | 5
    #    Tile 4  | 2
    #    Tile 5  | 2
    #    Tile 6  | 2
    #    Tile 7  | 3
    #    Total   | 20

    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
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
  Scenario: Log in as student and check/uncheck activities - results correctly reach database
    When I log in as "student1"
    And format_tiles progress indicator is showing as "percent" for course "Course 1"
    And I am on "Course 1" course homepage
    And format_tiles subtiles are off for course "Course 1"
    And I click on tile "1"
    And I wait until the page is ready

    And I click format tiles progress indicator for "Test page 1"
    Then format_tiles progress indicator for "Test page 1" in "Course 1" is "1" in the database

    And I click format tiles progress indicator for "Test page 2"
    Then format_tiles progress indicator for "Test page 2" in "Course 1" is "1" in the database

    And I click format tiles progress indicator for "Test page 1"
    Then format_tiles progress indicator for "Test page 1" in "Course 1" is "0" in the database

    And I click format tiles progress indicator for "Test page 2"
    Then format_tiles progress indicator for "Test page 2" in "Course 1" is "0" in the database

  @javascript
  Scenario: Log in as student and check/uncheck activities - results correctly shown in UI
    When I log in as "student1"
    And format_tiles progress indicator is showing as "percent" for course "Course 1"
    And I am on "Course 1" course homepage
    And format_tiles subtiles are off for course "Course 1"
    And I click on tile "1"
    And I wait until the page is ready

    And I click format tiles progress indicator for "Test page 1"
    Then format_tiles progress indicator for "Test page 1" in "Course 1" is "1" in the database

    And I click format tiles progress indicator for "Test page 2"
    Then format_tiles progress indicator for "Test page 2" in "Course 1" is "1" in the database

    And I click format tiles progress indicator for "Test page 1"
    Then format_tiles progress indicator for "Test page 1" in "Course 1" is "0" in the database

    And I click format tiles progress indicator for "Test page 2"
    Then format_tiles progress indicator for "Test page 2" in "Course 1" is "0" in the database

#    TODO check that the completion values shown on the tile and overall are complete when these items change