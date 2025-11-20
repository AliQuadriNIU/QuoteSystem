# Seed data for the sales associates table.
INSERT INTO SALES_ASSOCIATES(NAME, PASSWORD, ADDRESS)
    VALUES("Name 1", "Password", "123 Address Lane"),
          ("Name 2", "1234", "Address 2");

# Seed data for the quotes table.
INSERT INTO QUOTES(CUSTOMER_ID, ASSOCIATE_ID, CUSTOMER_EMAIL, PRICE)
    VALUES(1, 1, "customer1@mail", 1234.56),
          (2, 2, "customer2@mail", 789);

# Seed data for the line items table.
INSERT INTO LINE_ITEMS(QUOTE_ID, ITEM_NUMBER, DESCRIPTION, PRICE)
    VALUES(1, 1, "Description 1", 617.28),
          (1, 2, "Description 2", 617.28),
          (2, 1, "Description 3", 394.5),
          (2, 2, "Description 4", 394.5);

# Seed data for the secret notes table.
INSERT INTO SECRET_NOTES(QUOTE_ID, NOTE_NUMBER, NOTE)
    VALUES(1, 1, "Secret note 1"),
          (1, 2, "Secret note 2"),
          (2, 1, "Secret note 3"),
          (2, 2, "Secret note 4");
