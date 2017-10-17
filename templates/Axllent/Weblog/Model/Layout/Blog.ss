<div class="typography">
	<% if $PaginatedList.Exists %>
		<% loop $PaginatedList %>
			<div class="blogentry">
				<% if $FeaturedImage %>
					<a href="$Link">$FeaturedImage.ScaleWidth(300)</a>
				<% end_if %>
				<h3><a href="$Link">$Title</a></h3>
				<p>
					Published $PublishDate.Month $PublishDate.DayOfMonth(true), $PublishDate.Year
				</p>
				<% if $Summary %>
					<p>$Summary</p>
				<% else %>
					<p>$Content.Summary</p>
				<% end_if %>
				<p><a href="$Link">Read more...</a></p>
			</div>
		<% end_loop %>
	<% end_if %>

	<% with $PaginatedList %>
		<% include BlogPagination %>
	<% end_with %>
</div>
