/*eslint-disable no-undef*/
// yours59_matomo_popular_posts.js

const yours59_pop_posts = {};
/* popular_post_data comes from wordpress */
/* demo version defines 2 dashboards. these have an id attribute */
yours59_pop_posts.dashboard_widget_cat3_sel = 'dashboard_popular_posts_cat3'
yours59_pop_posts.dashboard_widget_cat4_sel = 'dashboard_popular_posts_cat4'

window.addEventListener('load', () => {
    const dashboard_widget_cat3 = document.getElementById(yours59_pop_posts.dashboard_widget_cat3_sel);

    if(dashboard_widget_cat3) {
        if(popular_post_data.data.data) {
            const markup = yours59_pop_posts.render_posts_backend(popular_post_data.data.data.cat3)        
            const inside = dashboard_widget_cat3.querySelector('.inside');
            inside.insertAdjacentHTML('afterbegin',markup)
        } else {
            // give some feedback to user with some info what went wrong
        }
    }
    
    const dashboard_widget_cat4 = document.getElementById(yours59_pop_posts.dashboard_widget_cat4_sel);
    if(dashboard_widget_cat4) {
        if(popular_post_data.data.data) {
            const markup = yours59_pop_posts.render_posts_backend(popular_post_data.data.data.cat4)        
            const inside = dashboard_widget_cat4.querySelector('.inside');
            inside.insertAdjacentHTML('afterbegin',markup)
        } else {
            // give some feedback to user with some info what went wrong
        }
    }

   
});

yours59_pop_posts.markup_post_admin = function (post) {
    return `
        <tr>
            <td>${post.hits}</td>
            <td><a href="${post.url}" target='_blank'>${post.title}</a></td>
        </tr>
    `;
}




yours59_pop_posts.render_posts_backend = function (posts) {
    if(!posts || posts.length == 0) {
        return;
    }
    let pops = ``;
    for (var i = 0; i < posts.length ; i++){
        pops += yours59_pop_posts.markup_post_admin(posts[i])
    }
   
    return `<table>
        <thead>
        <tr>
            <td>Aufrufe</td>
            <td>Post</td>
        </tr>
        </thead>
        <tbody>
            ${pops}
        </tbody>
    </table>`;
}