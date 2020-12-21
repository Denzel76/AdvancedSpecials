# AdvancedSpecials
Advanced specials and discount handling for osCommerce CE Phoenix (updated by Denzel)
-----------------------------------------------------

This add lets you easily administrate your special offers and discounts.

Features:

Filter products by categories or manufacturers and apply discounts to multiple products in one go.
Direct inline editing for each single product.
Set up a start and expire date.

---------
INSTALLATION:

1. Take a BACKUP of your data and database.

2. Copy the following files in the corresponding folder (no core files will be overwritten):

/admin/advanced_specials.php
/admin/includes/languages/english/advanced_specials.php
/admin/includes/boxes/catalog_advanced_specials.php
/admin/includes/languages/english/modules/boxes/advanced_specials.php
/includes/classes/adv_specials.php

3. At the first time the admin page is opened, the necessary database changes are made automatically.
   (an additional field: "start_date" will be added to the specials table and the start_specials hook
   is placed into the hooks table.)   

4. No files to edit. Just have fun ;)

=========================================================================================
That's it. Enjoy!
=========================================================================================
