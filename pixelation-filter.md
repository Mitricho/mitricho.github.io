  <filter id="pixelate" x="0" y="0">
    <feFlood x="4" y="4" height="2" width="2"/>
    <feComposite width="10" height="10"/>
    <feTile result="a"/>
    <feComposite in="SourceGraphic" in2="a" operator="in"/>
    <feMorphology operator="dilate" radius="5"/>
  </filter>

  filter="url(#pixelate)"
