# Configuring SilverStripe Weblog

In your CMS you need to add a `Blog` page, in which you will be able to add Blog Posts.

To adjust the number of blog enties listed per page, edit the page's settings
(Blog posts per page, default 10).

Make sure your user is either `ADMIN`, or is part of the `CMS_ACCESS_Weblog` group.

## Templating

SilverStripe Weblog comes with a very simplistic template and you are going to need
to customise this for your own website.

### Main templates

In your theme folder (eg: `themes/mysite/`) create the folder structure `Axllent/Weblog/Model/Layout`
so you end up with `themes/mysite/Axllent/Weblog/Model/Layout` and copy the Blog.ss and BlogPost.ss
to that folder. Edit as necessary.

### Blog pagination

To customise the BlogPagination template copy the `templates/Includes/BlogPagination.ss` to
`themes/mysite/Includes/BlogPagination.ss` and edit.
