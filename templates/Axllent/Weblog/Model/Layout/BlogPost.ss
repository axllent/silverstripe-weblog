<div class="typography blog-post">
	<header>
		<h1>$Title</h1>
	</header>
	<p class="blog-published-on">
		$PublishDate.Month $PublishDate.DayOfMonth(true), $PublishDate.Year
		<%-- requires silverstripe-weblog-categories module --%>
		<% if $Categories %>
			&nbsp; | &nbsp;
			<% loop $Categories %>
				<a href="$Link">$Title</a>
				<% if $Last %>
				<% else %>,
				<% end_if %>
			<% end_loop %>
		<% end_if %>
	</p>

	<% if $FeaturedImage %>
		$FeaturedImage
	<% end_if %>

	$Content
</div>
