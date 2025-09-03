<div class="speedbar">
    <div class="over">
        <span itemscope itemtype="https://schema.org/BreadcrumbList" id="dle-speedbar">

            <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <a itemprop="item" href="{site-url}"><span itemprop="name">{site-name}</span></a>
                <meta itemprop="position" content="1">
            </span>

            {separator}

            [second]
                <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a href="{filter-url}" itemprop="item"><span itemprop="name">{filter-name}</span></a>
                    <meta itemprop="position" content="2">
                </span>
                {separator} {page-descr} {page}
            [/second]
            [first]
                {filter-name}
            [/first]
        </span>
    </div>
</div>