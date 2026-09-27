# Retail shops and staff

Create each shop as a branch under the same business in Retail > Branches. Products belong to the business, so every shop uses the same catalogue.

In Administration, create or edit each employee account and assign its Branch. Employees use their own existing login credentials. Retail checkout resolves the assignment from their current business membership on every request, including after a new login or reassignment.

Assigned employees can sell only from their allocated active shop. Accounts without an assignment can select an active shop within the business. When only one active shop exists, checkout selects it automatically; for a business with no branches, its first sale creates Main Shop.

Every new retail POS sale records its shop and the authenticated employee. A submitted cashier ID cannot change the seller. Cash drawers must belong to that employee and shop and be open. Recent Transactions and All Transactions display the shop and employee. Older transactions without attribution display Not recorded.

Product stock continues to use the existing shared business stock workflow. This change does not introduce separate stock quantities for each shop.
