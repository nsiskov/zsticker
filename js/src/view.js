
/**
 * @constructor
 */
function StickerView(options) {
  
  var self = this;
  
  var canvas = null;
  var dc = null;
  var controller = null;
  var viewType = null;
  
  var rows = 0;
  var columns = 0;
  var topOffset = 0;
  var leftOffset = 0;
  var maxColumn = 0;
  
  var rectSize = 27;
  var rectSpace = 3;
  
  var stickerImage = new Image();
  stickerImage.src = options.stickerImage;
  
  var stickerData = null;
  
  this.getColumns = function () {
    return columns;
  };
  
  //remember the hover area
  var hoverArea = null;
  
  this.init = function(_controller, _viewType) {
    controller = _controller;
    viewType = _viewType;
    canvas = document.getElementById(options.canvasId);
    dc = canvas.getContext("2d");
    canvas.width = calculateWidth(canvas);
    columns = canvas.width / (rectSize + rectSpace);
    canvas.addEventListener('mousemove', onMouseMove, false);
    canvas.addEventListener('click', onMouseClick, false);
  };
  
  function calculateWidth(canvas) {
    var parentWidth = $(canvas).parent().width();
    if (parentWidth >= 600) {
      return 600;
    } else if (parentWidth >= 450) {
      return 450;
    } else {
      return 300;
    }
  }
  
  this.load = function(stickerCount, _stickerData) {
    stickerData = _stickerData;
    loadInternal();
  };
  
  this.resize = function() {
    var width = calculateWidth(canvas);
    if (width == canvas.width) {
      return null;
    }
    
    canvas.width = width;
    columns = canvas.width / (rectSize + rectSpace);
    controller.translateCoords();
    loadInternal();
  };
  
  function loadInternal() {
    hoverArea = new function() {
      this.x = 0;
      this.y = 0;
      this.selected = false;
    };
    rows = Math.ceil(stickerData.length / columns);
    maxColumn = columns - ((rows * columns) - stickerData.length);
    if (maxColumn == 0) {
      maxColumn = columns;
    }
    canvas.height = Math.ceil(rows * (rectSize + rectSpace));
    topOffset = rectSpace / 2;
    leftOffset = rectSpace / 2;
    drawAll(stickerData);
  }
  
  function drawAll(stickerData) {
    for (var i = 0; i < stickerData.length; i++) {
      drawRect(stickerData[i].coords.x, stickerData[i].coords.y,
          stickerData[i].stickerNumber, stickerData[i].marked);
    }
  }
  
  function onMouseMove(event) {
    if (viewType.selectable) {
      var coords = getMousePoints(event.pageX, event.pageY);
      if (hoverArea.selected == true && hoverArea.x == coords.x
          && hoverArea.y == coords.y) {
        return;
      }
      clearRect();
      if (coords.isInsideRect()) {
        markHover(coords.x, coords.y);
      }
    }
  }

  function clearRect() {
    if (hoverArea.selected == true) {
      var pointInfo = stickerData[hoverArea.y * columns + hoverArea.x];
      self.reDrawRect(hoverArea.x, hoverArea.y, pointInfo.marked, pointInfo.stickerNumber);
      hoverArea.selected = false;
    }
  }
  
  function markHover(x, y) {
    hoverArea.selected = true;
    hoverArea.x = x;
    hoverArea.y = y;
    drawHoverRect(x, y);
  }
  
  function onMouseClick(event) {
    var coords = getMousePoints(event.pageX, event.pageY);
    
    controller.onMouseClick(coords);
  }

  function drawRect(startX, startY, text, mark) {
    var devicePoint = getDevicePoints(startX, startY);
    
    var properties = null;
    if (mark) {
      properties = viewType.marked;
    } else {
      properties = viewType.empty;
    }
    
    //render image
    var alpha = dc.globalAlpha;
    dc.globalAlpha = properties.backgroundAlpha;
    dc.drawImage(stickerImage, devicePoint.x, devicePoint.y);
    dc.globalAlpha = alpha;
    
    //render rect
    var lineWidth = dc.lineWidth; 
    dc.lineWidth = 1;
    dc.strokeStyle = properties.strokeStyle;
    dc.strokeRect(devicePoint.x, devicePoint.y, rectSize, rectSize);
    dc.lineWidth = lineWidth;
    
    //render text
    dc.textAlign = properties.textAlign;
    dc.font= properties.textFont;
    dc.fillStyle = properties.textColor;;
    dc.fillText(text, devicePoint.x + (rectSize / 2), devicePoint.y
        + (rectSize / 2) + 3, 30);
  }

  function drawHoverRect(rX, rY) {
    var device = getDevicePoints(rX, rY);

    var alpha = dc.globalAlpha;
    dc.globalAlpha = 0.5;
    dc.fillStyle = "#999999";
    dc.fillRect(device.x, device.y, rectSize, rectSize);
    dc.globalAlpha = alpha;
  };

  this.reDrawRect = function(x, y, overMarked, text) {
    var point = getDevicePoints(x, y);
    
    dc.fillStyle = "#FFFFFF";
    dc.fillRect(point.x - 1, point.y - 1, rectSize + 2, rectSize + 2);
    // reDraw it again
    drawRect(x, y, text, overMarked);
  };

  function getDevicePoints(startX, startY) {
    return {
      x : startX * (rectSize + rectSpace) + leftOffset,
      y : startY * (rectSize + rectSpace) + topOffset
    };
  }

  function getMousePoints(x, y) {
    var mouseX = x - $(canvas).offset().left;
    var mouseY = y - $(canvas).offset().top;
    var regionX = Math.ceil(mouseX / (rectSize + rectSpace));
    var regionY = Math.ceil(mouseY / (rectSize + rectSpace));
    var rX = regionX - 1;
    var rY = regionY - 1;
    return {
      x : rX,
      y : rY,
      isInsideRect : function() {
        var point = getDevicePoints(rX, rY);

        if (mouseX > point.x && mouseX < point.x + rectSize && mouseY > point.y
            && mouseY < point.y + rectSize) {
          if (rY == rows - 1 && rX > maxColumn - 1) {
            return false;
          }
          return true;
        } else {
          return false;
        }
      }
    };
  }
}
StickerView.ViewType = {
    missing : {
      name : 'missing',
      marked: {strokeStyle : "#FF0000", backgroundAlpha : 0.3, textColor : "#000000",textFont : "bold 14px sans-serif", textAlign : "center"}, 
      empty: {strokeStyle : "#5353f7", backgroundAlpha : 0.8, textColor : "#FFFFFF", textFont : "bold 12px sans-serif", textAlign : "center"},
      selectable: true
    },
    duplicate : {
      name : 'duplicate',
      marked :{strokeStyle : "#5353f7",backgroundAlpha : 0.8,textColor : "#FFFFFF",textFont : "bold 12px sans-serif", textAlign : "center"},
      empty : {strokeStyle : "#FFFFFF",backgroundAlpha : 0.3,textColor : "#000000",textFont : "bold 14px sans-serif", textAlign : "center"},
      selectable: true
    },
    match : {
      name : 'match',
      marked : {strokeStyle : "#5353f7",backgroundAlpha : 0.8,textColor : "#FFFFFF",textFont : "bold 12px sans-serif", textAlign : "center"},
      empty : {strokeStyle : "#FF0000",backgroundAlpha : 0.3,textColor : "#000000",textFont : "bold 14px sans-serif", textAlign : "center"},
      selectable: false
    }
  };

window['StickerView'] = StickerView;
StickerView['ViewType'] = StickerView.ViewType;