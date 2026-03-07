Feature: Add products to cart
  As a user
  I want to add products to my cart
  So that I can create an order

  Scenario: User adds a product to an empty cart
    Given there exist following users:
      | id | name      |
      | 1  | Test User |
    And there exist following products:
      | id | code | name       | type | price | taxRate |
      | 1  | P001 | Product 1  | book | 1000  | 23      |
    When user "1" adds product "1" to the cart
        """
        {
            "userId": 1,
            "productId": 1,
            "quantity": 1
        }
        """
    Then the response should be received with status code 200
    And the response should equal json:
        """
        {
          "id": 1,
          "itemsTotal": 1000,
          "adjustmentsTotal": 0,
          "total": 1000,
          "items": [
            {
              "id": 1,
              "product": {
                "id": 1,
                "name": "Product 1",
                "price": 1000
              },
              "quantity": 1,
              "unitPrice": 1000,
              "total": 1000
            }
          ],
          "status": "cart"
        }
        """

  Scenario: User adds the same product twice and the quantity increases
    Given there exist following users:
      | id | name      |
      | 2  | Test User |
    And there exist following products:
      | id | code | name       | type | price | taxRate |
      | 2  | P002 | Product 2  | book | 2000  | 23      |
    When user "2" adds product "2" to the cart twice
        """
        {
            "userId": 2,
            "productId": 2,
            "quantity": 1
        }
        """
    Then the response should be received with status code 200
    And the response should equal json:
        """
        {
          "id": 2,
          "itemsTotal": 4000,
          "adjustmentsTotal": 0,
          "total": 4000,
          "items": [
            {
              "id": 2,
              "product": {
                "id": 2,
                "name": "Product 2",
                "price": 2000
              },
              "quantity": 2,
              "unitPrice": 2000,
              "total": 4000
            }
          ],
          "status": "cart"
        }
        """