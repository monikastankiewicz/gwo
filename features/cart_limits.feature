Feature: Cart limits
  As a system
  I want to limit products in the cart
  So that the cart stays valid

  Scenario: User cannot add more than the maximum quantity of a product already in the cart
    Given there exist following users:
      | id | name      |
      | 3  | Test User |
    And there exist following products:
      | id | code | name       | type | price | taxRate |
      | 3  | P003 | Product 3  | book | 1000  | 23      |
    And there exists a cart for user "3" with items:
      | productId | quantity |
      | 3         | 10       |
    When user "3" adds product "3" to the cart
        """
        {
            "userId": 3,
            "productId": 3,
            "quantity": 1
        }
        """
    Then the response should be received with status code 422
    And the product "3" in user "3" cart should still have quantity 10

  Scenario: User cannot add more than the maximum number of distinct products to the cart
    Given there exist following users:
      | id | name      |
      | 4  | Test User |

    And there exist following products:
      | id | code | name       | type | price | taxRate |
      | 4  | P004 | Product 4  | book | 1000  | 23      |
      | 5  | P005 | Product 5  | book | 2000  | 23      |
      | 6  | P006 | Product 6  | book | 3000  | 23      |
      | 7  | P007 | Product 7  | book | 4000  | 23      |
      | 8  | P008 | Product 8  | book | 5000  | 23      |
      | 9  | P009 | Product 9  | book | 6000  | 23      |

    And there exists a cart for user "4" with items:
      | productId | quantity |
      | 4         | 1        |
      | 5         | 1        |
      | 6         | 1        |
      | 7         | 1        |
      | 8         | 1        |

    When user "4" adds product "9" to the cart
      """
      {
        "userId": 4,
        "productId": 9,
        "quantity": 1
      }
      """
    Then the response should be received with status code 422
    And the user "4" cart should still contain 5 distinct products