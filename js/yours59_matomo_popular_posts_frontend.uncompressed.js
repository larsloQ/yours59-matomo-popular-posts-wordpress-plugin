const yours59_pop_posts = {};

/* popular_post_data comes from wordpress */
// console.log(popular_post_data, "popular_post_data");

window.addEventListener('load',function(){
    /* js writes pop-posts data into a element with ID #meistgelesen (german for most-read) 
     which comes from theme-template (sidebar.php or similar) */
    /* --adjust-- */
    const meisgelesen = document.getElementById("meistgelesen");
    /* since when have a filter based on category-id here, 
     * the template file appends the current (single) post category as an attribute
     */
    if(meisgelesen && meisgelesen.getAttribute('data-cat')) {
        if(popular_post_data.data.data) {
            /* */
            const cat = meisgelesen.getAttribute('data-cat');
            const markup = yours59_pop_posts.render_posts(popular_post_data.data.data[cat])        
            const inside = meisgelesen.querySelector('.inside');
            inside.insertAdjacentHTML('afterbegin',markup)
        } else {
            /* maybe remove meistgelesen ?*/
        }
    }
})

yours59_pop_posts.markup_post = function (post) {
    return `
        <div class='mostblock'>
            <a rel='nofollow' href="${post.url}" ><h3>${post.title}</h3></a></td>
            <div class="image_wrap">
                <img data-src="${post.image}" class="lazyload"></img>
            </div>
            <span>${post.teaser}</span>
            <a rel='nofollow' class="weiterlesen" href="${post.url}" >lesen</a></td>
        </div>
    `;
}

yours59_pop_posts.render_posts = function (posts) {
    if(!posts || posts.length == 0) {
        return;
    }
    let pops = ``;
    for (var i = 0; i < posts.length && i < 3; i++){
            pops += yours59_pop_posts.markup_post(posts[i])
    }
    return `${pops}`;
}