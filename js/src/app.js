/*
function checkFbLoginStatus() {
  FB.init({
    appId: '311732295699571',
    xfbml: true,
    status: true,
    cookie: true,
    version: 'v2.0'
  });
  FB.getLoginStatus(checkLogin);
}
*/ 
function checkLogin(response)  {
  console.log(response);
  if (response.status === 'connected') {
    // the user is logged in and has authenticated your
    // app, and response.authResponse supplies
    // the user's ID, a valid access token, a signed
    // request, and the time the access token 
    // and signed request each expire
    var uid = response.authResponse.userID;
    var accessToken = response.authResponse.accessToken;
    alert('userId: ' +uid + 'accessToken: ' + accessToken);
  } else if (response.status === 'not_authorized') {
    // the user is logged in to Facebook, 
    // but has not authenticated your app
    alert('Logged in to FB, not authorized');
    /*FB.login(function(response) {
      console.log(response);
    }, {scope: 'public_profile,email'}); */
  } else {
    alert('not logged in to FB');
  }
 }

function loadDashboardComponent(canvasId, viewType, albumId, stickerCount) {
  var stickerController = new StickerController({
    canvasId: canvasId,
    stickerImage: 'img/sticker.png',
    viewType: viewType,
    serviceUrl: 'service.html',
    albumId: albumId,
    stickerCount: stickerCount,
    usersContainerId: 'users'
  });
  
  $(window).on('load', function() {
    stickerController.init();
    $('body').scrollTop(1);
  });

  $(window).resize(function() {
    stickerController.resize();
  });
}
window['loadDashboardComponent'] = loadDashboardComponent;