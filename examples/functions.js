function getWeatherIconElement(formattedDate) {
  date = new Date(formattedDate);
  var diff = date.getTime() - new Date().getTime()
  var diffDays = Math.ceil(diff / (1000 * 3600 * 24))
  if (diffDays < 0 || diffDays > 5) {
      return "https://www.example.com/site/wp-content/plugins/owproxy/assets/unknown_weather.png"
  }
  else {
      var owmproxyUrl = `https://www.example.com/site/wp-json/owmp/v1/forecast/5/daily?n=${diffDays}`
      var request = new XMLHttpRequest();
      request.open('GET', owmproxyUrl, false)
      request.send(null)
      if (request.status === 200) {
          text = request.responseText
          url = JSON.parse(text)['item']
          return url
      }
      else {
          return "https://www.example.com/site/wp-content/plugins/owproxy/assets/unknown_weather.png"
      }
  }
};
