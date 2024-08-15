# City Mapper
WordPress Plugin to categorize and display local businesses, attractions, and accommodations to provide users with a detailed city guide.

## == Installation ==
Step 1: Install the plugin via the WordPress admin dashboard.  
Step 2: Activate the plugin.  
Step 3: Go to the City Mapper menu in the WordPress admin dashboard to import default terms and example posts.  
Step 4: Create a new post and assign the "City Location" category to it.  
Step 5: Use the shortcode [city_mapper] and its various parameters to display the city guide on your page.  

## == Shortcode ==  
### `[city_mapper]`: This will display a section with tabs and links to separate categories and sub-categories archive pages. 

### `[city_mapper category="" sub_category="" posts_per_page="" orderby="" order=""]`  
### == Parameters ==  
#### `category`: The main category to display. If not specified, all main categories will be displayed. If only one main category is present, the sub-categories will be displayed with links to their archive pages. Additionally, we will display the posts assigned to the main category.   
#### `sub_category`: Sub Category to display. When selected, we will display sibling sub-categories along with the posts assigned to the sub-category.  
#### `posts_per_page`: The number of posts to display per page.
#### `orderby`: The field to order the posts by.
#### `order`: The order to display the posts in.






## == Plugin Info ==  
Contributors: Zachary Dodd  
Requires at least: 6.1  
Tested up to: 6.6.1  
Stable tag: 6.6.1  
Requires PHP: 8.2  
License: All Rights Reserved.  Property of Polyglot Labs. 

## == Changelog ==
0.1.1 - Initial Release  
* Added CPT and Taxonomies
* Added Admin Menu with a feature to import default terms and example posts
