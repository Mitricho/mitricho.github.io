# C++ Code to Monitor USB Device Connections on Linux using libudev

Here's a complete C++ program that monitors USB device connections and disconnections on Linux using libudev and udev_monitor:

```cpp
#include <libudev.h>
#include <stdio.h>
#include <stdlib.h>
#include <locale.h>
#include <unistd.h>
#include <string>
#include <iostream>

class UdevMonitor {
public:
    UdevMonitor() : udev(nullptr), monitor(nullptr), mon_fd(-1) {}
    
    ~UdevMonitor() {
        cleanup();
    }
    
    bool initialize() {
        // Create the udev object
        udev = udev_new();
        if (!udev) {
            std::cerr << "Failed to create udev context" << std::endl;
            return false;
        }
        
        // Set up a monitor to watch for USB devices
        monitor = udev_monitor_new_from_netlink(udev, "udev");
        if (!monitor) {
            std::cerr << "Failed to create udev monitor" << std::endl;
            return false;
        }
        
        // Filter for USB devices
        udev_monitor_filter_add_match_subsystem_devtype(monitor, "usb", NULL);
        udev_monitor_filter_add_match_subsystem_devtype(monitor, "usb_device", NULL);
        
        // Enable the monitor
        if (udev_monitor_enable_receiving(monitor) < 0) {
            std::cerr << "Failed to enable udev monitor" << std::endl;
            return false;
        }
        
        // Get the file descriptor for the monitor
        mon_fd = udev_monitor_get_fd(monitor);
        if (mon_fd < 0) {
            std::cerr << "Failed to get monitor file descriptor" << std::endl;
            return false;
        }
        
        return true;
    }
    
    void run() {
        if (mon_fd < 0) {
            std::cerr << "Monitor not initialized" << std::endl;
            return;
        }
        
        std::cout << "Monitoring USB devices. Press Ctrl+C to exit..." << std::endl;
        
        // Main loop to monitor events
        while (true) {
            fd_set fds;
            struct timeval tv;
            int ret;
            
            FD_ZERO(&fds);
            FD_SET(mon_fd, &fds);
            tv.tv_sec = 0;
            tv.tv_usec = 0;
            
            ret = select(mon_fd + 1, &fds, NULL, NULL, &tv);
            if (ret > 0 && FD_ISSET(mon_fd, &fds)) {
                // Get the device that triggered the event
                struct udev_device* dev = udev_monitor_receive_device(monitor);
                if (dev) {
                    handle_device_event(dev);
                    udev_device_unref(dev);
                }
            }
            
            // Small delay to prevent high CPU usage
            usleep(100000); // 100ms
        }
    }
    
private:
    void handle_device_event(struct udev_device* dev) {
        const char* action = udev_device_get_action(dev);
        const char* vendor = udev_device_get_sysattr_value(dev, "idVendor");
        const char* product = udev_device_get_sysattr_value(dev, "idProduct");
        const char* devpath = udev_device_get_devpath(dev);
        const char* devnode = udev_device_get_devnode(dev);
        
        if (!action) return;
        
        std::cout << "USB Device Event: " << action << std::endl;
        
        if (vendor && product) {
            std::cout << "  Vendor:Product ID: " << vendor << ":" << product << std::endl;
        }
        
        if (devpath) {
            std::cout << "  Device Path: " << devpath << std::endl;
        }
        
        if (devnode) {
            std::cout << "  Device Node: " << devnode << std::endl;
        }
        
        // Print all properties (for debugging)
        /*
        struct udev_list_entry* properties = udev_device_get_properties_list_entry(dev);
        struct udev_list_entry* entry;
        udev_list_entry_foreach(entry, properties) {
            std::cout << "  Property: " 
                      << udev_list_entry_get_name(entry) << "=" 
                      << udev_list_entry_get_value(entry) << std::endl;
        }
        */
        
        std::cout << std::endl;
    }
    
    void cleanup() {
        if (monitor) {
            udev_monitor_unref(monitor);
            monitor = nullptr;
        }
        
        if (udev) {
            udev_unref(udev);
            udev = nullptr;
        }
        
        mon_fd = -1;
    }
    
    struct udev* udev;
    struct udev_monitor* monitor;
    int mon_fd;
};

int main() {
    UdevMonitor monitor;
    
    if (!monitor.initialize()) {
        return EXIT_FAILURE;
    }
    
    monitor.run();
    
    return EXIT_SUCCESS;
}
```

## How to Compile and Run

1. Save this code to a file named `usb_monitor.cpp`
2. Compile it with:
   ```bash
   g++ -std=c++11 usb_monitor.cpp -o usb_monitor -ludev
   ```
3. Run it (may need sudo for some device information):
   ```bash
   ./usb_monitor
   ```

## Features

1. Monitors both USB device connections and disconnections
2. Displays vendor and product IDs for USB devices
3. Shows device paths and nodes
4. Uses proper resource cleanup with RAII pattern
5. Includes error handling for udev operations

## Notes

- The program will run indefinitely until you stop it with Ctrl+C
- You can uncomment the properties printing section to see all available device properties
- For production use, you might want to add more robust error handling and logging
- The code filters specifically for USB devices, but you can modify the filter to monitor other device types

Would you like me to explain any specific part of the code in more detail or make any modifications?
