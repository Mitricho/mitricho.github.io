```
<filter id="pixelate" x="0%" y="0%" width="100%" height="100%">
  <feGaussianBlur stdDeviation="2" in="SourceGraphic" result="smoothed" />
  <feImage width="15" height="15" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAIAAAACDbGyAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAWSURBVAgdY1ywgOEDAwKxgJhIgFQ+AP/vCNK2s+8LAAAAAElFTkSuQmCC" result="displacement-map" />
  <feTile in="displacement-map" result="pixelate-map" />
  <feDisplacementMap in="smoothed" in2="pixelate-map" xChannelSelector="R" yChannelSelector="G" scale="50" result="pre-final"/>
  <feComposite operator="in" in2="SourceGraphic"/>
</filter>
```

To apply:
```
  filter="url(#pixelate)"
```
