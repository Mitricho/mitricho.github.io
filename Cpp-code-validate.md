#include <iostream>
#include <string>
#include <regex>

int main() {
    // Example 1: Validating a simple identifier (starts with letter/underscore, followed by alphanumeric/underscore)
    std::string identifier1 = "myVariable";
    std::string identifier2 = "_another_one";
    std::string identifier3 = "1invalid"; // Invalid
    std::regex identifier_pattern("^[a-zA-Z_][a-zA-Z0-9_]*$");

    if (std::regex_match(identifier1, identifier_pattern)) {
        std::cout << identifier1 << " is a valid identifier." << std::endl;
    } else {
        std::cout << identifier1 << " is NOT a valid identifier." << std::endl;
    }

    if (std::regex_match(identifier3, identifier_pattern)) {
        std::cout << identifier3 << " is a valid identifier." << std::endl;
    } else {
        std::cout << identifier3 << " is NOT a valid identifier." << std::endl;
    }

    // Example 2: Validating a simple email format (basic check)
    std::string email1 = "user@example.com";
    std::string email2 = "invalid-email";
    std::regex email_pattern(R"(^[\w.-]+@[\w.-]+\.[a-zA-Z]{2,4}$)"); // Raw string literal for convenience

    if (std::regex_match(email1, email_pattern)) {
        std::cout << email1 << " is a valid email." << std::endl;
    } else {
        std::cout << email1 << " is NOT a valid email." << std::endl;
    }

    return 0;
}
