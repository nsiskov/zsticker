/**
 * @constructor
 */
function StickerController(options) {
  
  var serviceURL = options.serviceUrl; 
  var albumId = options.albumId;
  var stickerCount = options.stickerCount;
  var view = new StickerView(options);
  var viewType = options.viewType;
  
  var stickerData = null;
  
  
  this.init = function() {
    view.init(this, viewType);
    var request = {
      type : viewType.name,
      action : "get",
      albumId : albumId
    };
    $.ajax({
      url : serviceURL,
      type : "POST",
      data : JSON.stringify(request),
      processData : "false"
    }).done(function(data) {
      var response = JSON.parse(data);
      
      switch (response.type) {
      case 'missing':
        stickerData = parseEditable(response.data);
        break;
      case 'duplicate':
        stickerData = parseEditable(response.data);
        var userData = parseUsers(response.data);
        renderUserData(userData);
        break;
      case 'match':
        stickerData = parseMatch(response.data);
        userData = parseUsers(response.data);
        renderUserData(userData);
        stickerCount = stickerData.length;
        break;
      }
      
      view.load(stickerCount, stickerData);
    });
  };
  
  this.resize = function() {
    view.resize();
  };
  
  function parseEditable(input) {
    var stickers = new Array();
    for (var i = 0; i < input.length; i++ ) {
      stickers = stickers.concat(input[i].stickers);
    }
    
    var retData = new Array();
    for (var i = 1; i <= stickerCount; i++ ) {
      retData[i - 1] = {
          stickerNumber: i,
          coords: getCoordsFromNumber(i),
          marked: stickers.indexOf(i + "") != -1
      };
    }
    return retData;
  }
  
  function parseMatch(input) {
    var allStickers = new Array();
    var matchedStickers = new Array();
    for (var i = 0; i < input.length; i++ ) {
      allStickers = allStickers.concat(input[i].stickers);
      if (input[i].uid != null) {
        matchedStickers = matchedStickers.concat(input[i].stickers);
      };
    }
    
    allStickers = allStickers.filter(function(elem, pos, self) {
        return self.indexOf(elem) == pos;
    });
    
    allStickers.sort(function(a,b) {return a - b;});
    
    var retData = new Array();
    for (var i = 0; i < allStickers.length; i++ ) {
      retData[i] = {
        stickerNumber: allStickers[i],
        coords: getCoordsFromNumber(i + 1),
        marked: matchedStickers.indexOf(allStickers[i]) != -1
      };
    }
    return retData;
  }
  
  function parseUsers(input) {
    var users = new Array();
    var cnt = 0;
    for (var i = 0; i < input.length; i++) {
      if (input[i].uid != null) {
        users[cnt] = {};
        users[cnt].uid = input[i].uid;
        users[cnt].fullname = input[i].fullname;
        var stickers = input[i].stickers;
        stickers.sort(function(a, b) {return a - b;});
        users[cnt].stickers = stickers;
        cnt++;
      }
    }
    return users;
  }
  
  function renderUserData(userData) {
    var parent = document.getElementById(options.usersContainerId);
    for (var i = 0; i < userData.length; i++) {
      var nameLi = document.createElement('li');
      var nametitle = document.createElement('strong');
      nametitle.innerHTML = userData[i].fullname;
      nameLi.appendChild(nametitle);
      
      var stickersUl = document.createElement('ul');
      stickersUl.setAttribute('class', 'list-inline');
      for (var j = 0; j < userData[i].stickers.length; j++) {
        var stickersLi = document.createElement('li');
        stickersLi.innerHTML = userData[i].stickers[j];
        stickersUl.appendChild(stickersLi);
      }
      nameLi.appendChild(stickersUl);
      
      parent.appendChild(nameLi);
    }
  }
  
  this.onMouseClick = function(coords) {
    if (coords.isInsideRect()) {
      var sticker = getStickerFromCoords(coords.x, coords.y);
      var numbers = new Array();
      numbers[0] = sticker.stickerNumber;
      var shouldAdd = !sticker.marked;

      var request = {
        type : viewType.name,
        action : "set",
        add : shouldAdd,
        albumId : albumId,
        stickerNumbers : numbers
      };
      $.ajax({
        url : serviceURL,
        type : "POST",
        data : JSON.stringify(request),
        processData : "false"
      }).done(function(data) {
        var result = JSON.parse(data);
        updateState(result.data.stickerNumbers, result.data.added);
      });
    }
  };
  
  function getStickerFromCoords(x, y) {
    return stickerData[y * view.getColumns() + x];
  }
  
  /// Map sticker number to representation coordinates
  function getCoordsFromNumber(number) {
    var x = (number % view.getColumns()) - 1;
    if (x == -1) {
      x = view.getColumns() - 1;
    }
    var y = Math.ceil(number / view.getColumns()) - 1;
    return {
      x: x,
      y: y
    };
  }
  ////
  
  this.translateCoords = function() {
    for (var i = 0; i < stickerData.length; i++) {
      stickerData[i].coords = getCoordsFromNumber(i + 1);
    }
  };
  
  function getStickerFromNumber(number) {
    var coords = getCoordsFromNumber(number);
    return getStickerFromCoords(coords.x, coords.y);
  }
  
  function updateState(stickerNumbers, added) {
    for (var i = 0; i < stickerNumbers.length; i++) {
      var stickerData = getStickerFromNumber(stickerNumbers[i]);
      if (stickerData.marked != added) {
        stickerData.marked = added;
        view.reDrawRect(stickerData.coords.x, stickerData.coords.y, 
            stickerData.marked, stickerData.stickerNumber);
      }
    }
  }
}