## Wine font smoothing

### In Wine:
1. Using Registry Edits:
  Open the Wine registry editor (e.g., wine regedit or winefile then regedit.exe). 
  Navigate to HKEY_CURRENT_USER\Control Panel\Desktop. 
  Modify the FontSmoothing value: 0 to disable, 1 to enable. 
  You can also create a FontSmoothingType value and set it to 2 for RGB subpixel smoothing. 
  For more specific control, you can also create a FontSmoothingGamma value (e.g., 1000 for default, lower values for darker fonts) and a FontSmoothingOrientation value (e.g., 1 for horizontal, 2 for vertical). 

### Using Winetricks:
  Install winetricks (e.g., sudo apt install winetricks on Debian/Ubuntu). 
  Use winetricks fontsmooth-rgb to enable RGB subpixel font smoothing. 
  You can also use winetricks fontsmooth-gray for greyscale smoothing. 
