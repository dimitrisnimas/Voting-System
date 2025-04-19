# Voting-System Plugin for WordPress

Technical Description of the Electronic Voting System

The voting system is implemented as a custom WordPress plugin, designed to ensure security, anonymity, and data integrity. It is a lightweight, server-side application that utilizes the existing WordPress MySQL database infrastructure to handle all voting-related operations.
Architecture:

    Token-Based Authentication:
    Each participant receives a unique, randomly generated, one-time-use token via email. This token acts as an anonymous access key. No user registration or login is required, and no personal data is stored.

    Database Structure:

        wp_voting_tokens: Stores issued tokens with fields for token (UNIQUE) and used (BOOLEAN).

        wp_voting_votes: Stores the actual votes with fields for vote_option and timestamp. There is no link between the vote and the token or voter identity.

    Anonymity Guarantee:
    When a vote is submitted, the corresponding token is marked as “used,” but the vote itself is stored separately with no identifying information. This ensures complete separation of identity verification and vote storage.

    Time-Based Access Control:
    The voting form is only accessible during the predefined voting window (May 10, from 10:00 to 22:00). Outside this timeframe, the form is disabled and a message is displayed to inform users.

    Live Results Mechanism:
    The results page uses AJAX polling (via admin-ajax.php) to dynamically display vote counts. This page is only enabled after the voting window closes, to prevent influencing voters during the process.

    Security Measures:

        All user inputs (e.g., tokens and form data) are sanitized using WordPress functions like sanitize_text_field() and prepare() to prevent SQL injection and cross-site scripting (XSS).

        Tokens are non-predictable, unique, and invalidated immediately after use.

        No names, email addresses, or IP addresses are stored or logged.

Advantages:

    High level of anonymity, with no user accounts, cookies, or session tracking required.

    Complies with data protection principles (GDPR-friendly).

    No reliance on third-party platforms or external services.

    Transparent and verifiable process with automatic result display after voting ends.
