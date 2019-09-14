# Description:

Mass downloads .torrent files from yst websites starting from the most recent page upto page limit or when it hits a certain movie title.

# Installation:

1. Rename config.php.init to config.php
2. Edit config.php file and fill up all the necessary information


# Documentation:
  - yts_movie_title_limit: Movie title where the parser will stop at and wont download.
  - yts_page_limit: regardless of other attributes, this will force the script to stop downloading at page limit.
  - yts_url: yts base url ex: https://domain.com/
  - yts_username: username used to login to yts website
  - yts_password: password used to login to yts website
