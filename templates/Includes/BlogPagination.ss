<% if $MoreThanOnePage %>
    <nav aria-label="navigation" class="page-nav">
        <ul class="pagination">
            <% if $NotFirstPage %>
                <li>
                    <a href="{$PrevLink}" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
            <% else %>
                <li class="disabled">
                    <span aria-hidden="true">&laquo;</span>
                </li>
            <% end_if %>

            <% loop $PaginationSummary(4) %>
                <% if $CurrentBool %>
                    <li class="active"><span>$PageNum</span></li>
                <% else %>
                    <% if $Link %>
                        <li><a href="$Link">$PageNum</a></li>
                    <% end_if %>
                <% end_if %>
            <% end_loop %>

            <% if $NotLastPage %>
                <li>
                    <a href="{$NextLink}" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            <% else %>
                <li class="disabled">
                    <span aria-hidden="true">&raquo;</span>
                </li>
            <% end_if %>
        </ul>
    </nav>
<% end_if %>
