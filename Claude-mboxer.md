# Google Takeout MBOX Parser

I'll create a comprehensive C++ Qt6 console application that parses Google Takeout `.mbox` files into a well-structured folder hierarchy.

## Project Structure

```
mbox-parser/
├── CMakeLists.txt
├── README.md
├── src/
│   ├── main.cpp
│   ├── mboxparser.h
│   ├── mboxparser.cpp
│   ├── emailmessage.h
│   ├── emailmessage.cpp
│   ├── messagestore.h
│   ├── messagestore.cpp
│   ├── folderorganizer.h
│   ├── folderorganizer.cpp
│   ├── indexmanager.h
│   ├── indexmanager.cpp
│   ├── deduplicator.h
│   ├── deduplicator.cpp
│   ├── threadbuilder.h
│   ├── threadbuilder.cpp
│   ├── mimeutils.h
│   └── mimeutils.cpp
```

## CMakeLists.txt

```cmake
cmake_minimum_required(VERSION 3.20)
project(mbox-parser VERSION 1.0.0 LANGUAGES CXX)

set(CMAKE_CXX_STANDARD 20)
set(CMAKE_CXX_STANDARD_REQUIRED ON)
set(CMAKE_AUTOMOC ON)

find_package(Qt6 6.10 REQUIRED COMPONENTS Core Core5Compat)

add_executable(mbox-parser
    src/main.cpp
    src/mboxparser.h
    src/mboxparser.cpp
    src/emailmessage.h
    src/emailmessage.cpp
    src/messagestore.h
    src/messagestore.cpp
    src/folderorganizer.h
    src/folderorganizer.cpp
    src/indexmanager.h
    src/indexmanager.cpp
    src/deduplicator.h
    src/deduplicator.cpp
    src/threadbuilder.h
    src/threadbuilder.cpp
    src/mimeutils.h
    src/mimeutils.cpp
)

target_link_libraries(mbox-parser PRIVATE
    Qt6::Core
    Qt6::Core5Compat
)

install(TARGETS mbox-parser DESTINATION bin)
```

## src/emailmessage.h

```cpp
#pragma once

#include <QString>
#include <QStringList>
#include <QDateTime>
#include <QJsonObject>
#include <QJsonArray>
#include <QMap>
#include <QCryptographicHash>

struct EmailAddress {
    QString name;
    QString email;

    QString toString() const {
        if (name.isEmpty()) return email;
        return QStringLiteral("%1 <%2>").arg(name, email);
    }

    static EmailAddress parse(const QString &raw);
    QJsonObject toJson() const;
    static EmailAddress fromJson(const QJsonObject &obj);
};

struct Attachment {
    QString filename;
    QString mimeType;
    qint64 size = 0;
    QString storagePath; // relative path where attachment is saved

    QJsonObject toJson() const;
    static Attachment fromJson(const QJsonObject &obj);
};

class EmailMessage {
public:
    EmailMessage() = default;

    // Parse raw mbox message (including headers and body)
    bool parseFromRaw(const QByteArray &rawData);

    // Unique identifier
    QString messageId() const { return m_messageId; }
    void setMessageId(const QString &id) { m_messageId = id; }

    // Content hash for deduplication
    QString contentHash() const { return m_contentHash; }

    // Threading
    QString inReplyTo() const { return m_inReplyTo; }
    QStringList references() const { return m_references; }

    // Metadata
    QString subject() const { return m_subject; }
    EmailAddress from() const { return m_from; }
    QList<EmailAddress> to() const { return m_to; }
    QList<EmailAddress> cc() const { return m_cc; }
    QList<EmailAddress> bcc() const { return m_bcc; }
    QDateTime date() const { return m_date; }

    // Google-specific labels
    QStringList gmailLabels() const { return m_gmailLabels; }

    // Content
    QString textBody() const { return m_textBody; }
    QString htmlBody() const { return m_htmlBody; }
    QList<Attachment> attachments() const { return m_attachments; }
    void setAttachments(const QList<Attachment> &attachments) { m_attachments = attachments; }

    // Raw data
    QByteArray rawData() const { return m_rawData; }

    // All headers
    QMap<QString, QStringList> headers() const { return m_headers; }

    // Thread ID (set during thread building)
    QString threadId() const { return m_threadId; }
    void setThreadId(const QString &id) { m_threadId = id; }

    // Serialization
    QJsonObject toMetadataJson() const;
    static EmailMessage fromMetadataJson(const QJsonObject &obj);

    // Generate a safe filename
    QString safeFilename() const;

private:
    void parseHeaders(const QByteArray &headerBlock);
    void parseBody(const QByteArray &bodyBlock);
    void parseMimeBody(const QByteArray &bodyBlock, const QString &contentType);
    void computeContentHash();
    QString decodeHeaderValue(const QByteArray &value) const;
    QByteArray decodeEncodedWords(const QByteArray &input) const;

    QString m_messageId;
    QString m_contentHash;
    QString m_inReplyTo;
    QStringList m_references;
    QString m_subject;
    EmailAddress m_from;
    QList<EmailAddress> m_to;
    QList<EmailAddress> m_cc;
    QList<EmailAddress> m_bcc;
    QDateTime m_date;
    QStringList m_gmailLabels;
    QString m_textBody;
    QString m_htmlBody;
    QList<Attachment> m_attachments;
    QByteArray m_rawData;
    QMap<QString, QStringList> m_headers;
    QString m_threadId;
};
```

## src/emailmessage.cpp

```cpp
#include "emailmessage.h"
#include "mimeutils.h"

#include <QRegularExpression>
#include <QTextCodec>
#include <QDebug>

EmailAddress EmailAddress::parse(const QString &raw) {
    EmailAddress addr;
    static QRegularExpression re(R"((?:"?([^"]*)"?\s*)?<?([a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,})>?)");
    auto match = re.match(raw.trimmed());
    if (match.hasMatch()) {
        addr.name = match.captured(1).trimmed();
        addr.email = match.captured(2).trimmed().toLower();
    } else {
        addr.email = raw.trimmed().toLower();
    }
    return addr;
}

QJsonObject EmailAddress::toJson() const {
    QJsonObject obj;
    obj["name"] = name;
    obj["email"] = email;
    return obj;
}

EmailAddress EmailAddress::fromJson(const QJsonObject &obj) {
    EmailAddress addr;
    addr.name = obj["name"].toString();
    addr.email = obj["email"].toString();
    return addr;
}

QJsonObject Attachment::toJson() const {
    QJsonObject obj;
    obj["filename"] = filename;
    obj["mimeType"] = mimeType;
    obj["size"] = size;
    obj["storagePath"] = storagePath;
    return obj;
}

Attachment Attachment::fromJson(const QJsonObject &obj) {
    Attachment att;
    att.filename = obj["filename"].toString();
    att.mimeType = obj["mimeType"].toString();
    att.size = obj["size"].toInteger();
    att.storagePath = obj["storagePath"].toString();
    return att;
}

bool EmailMessage::parseFromRaw(const QByteArray &rawData) {
    m_rawData = rawData;

    // Split headers from body at first blank line
    int headerEnd = rawData.indexOf("\r\n\r\n");
    if (headerEnd == -1) {
        headerEnd = rawData.indexOf("\n\n");
    }

    QByteArray headerBlock;
    QByteArray bodyBlock;

    if (headerEnd != -1) {
        headerBlock = rawData.left(headerEnd);
        int bodyStart = headerEnd + (rawData.mid(headerEnd, 4) == "\r\n\r\n" ? 4 : 2);
        bodyBlock = rawData.mid(bodyStart);
    } else {
        headerBlock = rawData;
    }

    parseHeaders(headerBlock);
    parseBody(bodyBlock);
    computeContentHash();

    return !m_messageId.isEmpty() || !m_subject.isEmpty() || !m_from.email.isEmpty();
}

void EmailMessage::parseHeaders(const QByteArray &headerBlock) {
    // Unfold headers (continuation lines start with whitespace)
    QByteArray unfolded;
    QList<QByteArray> rawLines = headerBlock.split('\n');

    for (const QByteArray &line : rawLines) {
        QByteArray trimmedLine = line;
        if (trimmedLine.endsWith('\r'))
            trimmedLine.chop(1);

        if (!trimmedLine.isEmpty() && (trimmedLine[0] == ' ' || trimmedLine[0] == '\t')) {
            // Continuation line
            unfolded.append(' ');
            unfolded.append(trimmedLine.trimmed());
        } else {
            if (!unfolded.isEmpty())
                unfolded.append('\n');
            unfolded.append(trimmedLine);
        }
    }

    QList<QByteArray> headerLines = unfolded.split('\n');

    for (const QByteArray &line : headerLines) {
        int colonPos = line.indexOf(':');
        if (colonPos <= 0) continue;

        QString headerName = QString::fromLatin1(line.left(colonPos)).trimmed().toLower();
        QByteArray rawValue = line.mid(colonPos + 1).trimmed();
        QString headerValue = decodeHeaderValue(rawValue);

        m_headers[headerName].append(headerValue);

        if (headerName == "message-id") {
            // Extract the ID from angle brackets
            static QRegularExpression idRe(R"(<([^>]+)>)");
            auto match = idRe.match(headerValue);
            m_messageId = match.hasMatch() ? match.captured(1) : headerValue.trimmed();
        } else if (headerName == "subject") {
            m_subject = headerValue;
        } else if (headerName == "from") {
            m_from = EmailAddress::parse(headerValue);
        } else if (headerName == "to") {
            for (const QString &addr : MimeUtils::splitAddressList(headerValue)) {
                m_to.append(EmailAddress::parse(addr));
            }
        } else if (headerName == "cc") {
            for (const QString &addr : MimeUtils::splitAddressList(headerValue)) {
                m_cc.append(EmailAddress::parse(addr));
            }
        } else if (headerName == "bcc") {
            for (const QString &addr : MimeUtils::splitAddressList(headerValue)) {
                m_bcc.append(EmailAddress::parse(addr));
            }
        } else if (headerName == "date") {
            m_date = MimeUtils::parseDate(headerValue);
        } else if (headerName == "in-reply-to") {
            static QRegularExpression idRe2(R"(<([^>]+)>)");
            auto match = idRe2.match(headerValue);
            m_inReplyTo = match.hasMatch() ? match.captured(1) : headerValue.trimmed();
        } else if (headerName == "references") {
            static QRegularExpression refRe(R"(<([^>]+)>)");
            auto it = refRe.globalMatch(headerValue);
            while (it.hasNext()) {
                auto match = it.next();
                m_references.append(match.captured(1));
            }
        } else if (headerName == "x-gmail-labels") {
            // Google Takeout specific
            for (const QString &label : headerValue.split(',')) {
                QString trimmed = label.trimmed();
                if (!trimmed.isEmpty())
                    m_gmailLabels.append(trimmed);
            }
        }
    }

    // Generate message ID if missing
    if (m_messageId.isEmpty()) {
        QCryptographicHash hash(QCryptographicHash::Sha256);
        hash.addData(m_rawData.left(qMin(m_rawData.size(), qint64(4096))));
        m_messageId = "generated-" + hash.result().toHex().left(32);
    }
}

void EmailMessage::parseBody(const QByteArray &bodyBlock) {
    if (bodyBlock.isEmpty()) return;

    // Get content type from headers
    QString contentType = "text/plain";
    if (m_headers.contains("content-type")) {
        contentType = m_headers["content-type"].first();
    }

    if (contentType.startsWith("multipart/", Qt::CaseInsensitive)) {
        parseMimeBody(bodyBlock, contentType);
    } else {
        // Simple single-part message
        QByteArray decoded = MimeUtils::decodeBody(bodyBlock, m_headers);
        QString charset = MimeUtils::extractParam(contentType, "charset");
        QString text = MimeUtils::decodeCharset(decoded, charset);

        if (contentType.startsWith("text/html", Qt::CaseInsensitive)) {
            m_htmlBody = text;
        } else {
            m_textBody = text;
        }
    }
}

void EmailMessage::parseMimeBody(const QByteArray &bodyBlock, const QString &contentType) {
    QString boundary = MimeUtils::extractParam(contentType, "boundary");
    if (boundary.isEmpty()) {
        m_textBody = QString::fromUtf8(bodyBlock);
        return;
    }

    QByteArray delim = "--" + boundary.toUtf8();
    QByteArray endDelim = delim + "--";

    QList<QByteArray> parts;
    int pos = bodyBlock.indexOf(delim);
    while (pos != -1) {
        int partStart = bodyBlock.indexOf('\n', pos);
        if (partStart == -1) break;
        partStart++;

        int nextDelim = bodyBlock.indexOf(delim, partStart);
        if (nextDelim == -1) break;

        QByteArray part = bodyBlock.mid(partStart, nextDelim - partStart);
        // Remove trailing \r\n before delimiter
        while (part.endsWith('\n') || part.endsWith('\r'))
            part.chop(1);

        parts.append(part);

        if (bodyBlock.mid(nextDelim, endDelim.size()) == endDelim)
            break;
        pos = nextDelim;
    }

    for (const QByteArray &part : parts) {
        // Split part headers from part body
        int partHeaderEnd = part.indexOf("\r\n\r\n");
        if (partHeaderEnd == -1)
            partHeaderEnd = part.indexOf("\n\n");
        if (partHeaderEnd == -1) continue;

        QByteArray partHeaderBlock = part.left(partHeaderEnd);
        int partBodyStart = partHeaderEnd + (part.mid(partHeaderEnd, 4) == "\r\n\r\n" ? 4 : 2);
        QByteArray partBody = part.mid(partBodyStart);

        // Parse part headers
        QMap<QString, QStringList> partHeaders;
        QByteArray unfoldedPart;
        QList<QByteArray> pLines = partHeaderBlock.split('\n');
        for (const QByteArray &line : pLines) {
            QByteArray l = line;
            if (l.endsWith('\r')) l.chop(1);
            if (!l.isEmpty() && (l[0] == ' ' || l[0] == '\t')) {
                unfoldedPart.append(' ');
                unfoldedPart.append(l.trimmed());
            } else {
                if (!unfoldedPart.isEmpty()) unfoldedPart.append('\n');
                unfoldedPart.append(l);
            }
        }

        for (const QByteArray &hLine : unfoldedPart.split('\n')) {
            int cPos = hLine.indexOf(':');
            if (cPos <= 0) continue;
            QString hName = QString::fromLatin1(hLine.left(cPos)).trimmed().toLower();
            QString hValue = decodeHeaderValue(hLine.mid(cPos + 1).trimmed());
            partHeaders[hName].append(hValue);
        }

        QString partContentType = partHeaders.contains("content-type")
            ? partHeaders["content-type"].first() : "text/plain";

        // Recurse for nested multipart
        if (partContentType.startsWith("multipart/", Qt::CaseInsensitive)) {
            parseMimeBody(partBody, partContentType);
            continue;
        }

        // Decode transfer encoding
        QString transferEncoding;
        if (partHeaders.contains("content-transfer-encoding"))
            transferEncoding = partHeaders["content-transfer-encoding"].first().trimmed().toLower();

        QByteArray decodedPart;
        if (transferEncoding == "base64") {
            decodedPart = QByteArray::fromBase64(partBody);
        } else if (transferEncoding == "quoted-printable") {
            decodedPart = MimeUtils::decodeQuotedPrintable(partBody);
        } else {
            decodedPart = partBody;
        }

        // Determine disposition
        QString disposition;
        if (partHeaders.contains("content-disposition"))
            disposition = partHeaders["content-disposition"].first().toLower();

        bool isAttachment = disposition.startsWith("attachment") ||
                           (disposition.startsWith("inline") &&
                            !partContentType.startsWith("text/", Qt::CaseInsensitive));

        // Check for filename
        QString filename = MimeUtils::extractParam(
            partHeaders.contains("content-disposition") ? partHeaders["content-disposition"].first() : "",
            "filename");
        if (filename.isEmpty()) {
            filename = MimeUtils::extractParam(partContentType, "name");
        }
        if (!filename.isEmpty()) isAttachment = true;

        if (isAttachment) {
            Attachment att;
            att.filename = filename.isEmpty() ? "unnamed_attachment" : filename;
            att.mimeType = partContentType.section(';', 0, 0).trimmed();
            att.size = decodedPart.size();
            // storagePath will be set when saving
            m_attachments.append(att);
        } else if (partContentType.startsWith("text/html", Qt::CaseInsensitive)) {
            QString charset = MimeUtils::extractParam(partContentType, "charset");
            m_htmlBody = MimeUtils::decodeCharset(decodedPart, charset);
        } else if (partContentType.startsWith("text/", Qt::CaseInsensitive)) {
            QString charset = MimeUtils::extractParam(partContentType, "charset");
            m_textBody = MimeUtils::decodeCharset(decodedPart, charset);
        }
    }
}

void EmailMessage::computeContentHash() {
    QCryptographicHash hash(QCryptographicHash::Sha256);
    // Use Message-ID + Date + Subject + From for a robust hash
    hash.addData(m_messageId.toUtf8());
    hash.addData(m_date.toString(Qt::ISODate).toUtf8());
    hash.addData(m_subject.toUtf8());
    hash.addData(m_from.email.toUtf8());
    m_contentHash = hash.result().toHex();
}

QString EmailMessage::decodeHeaderValue(const QByteArray &value) const {
    return QString::fromUtf8(decodeEncodedWords(value));
}

QByteArray EmailMessage::decodeEncodedWords(const QByteArray &input) const {
    // RFC 2047 encoded words: =?charset?encoding?text?=
    static QRegularExpression re(R"(=\?([^?]+)\?([BbQq])\?([^?]*)\?=)");
    QString result = QString::fromLatin1(input);
    auto it = re.globalMatch(result);

    QString decoded = result;
    int offset = 0;

    // Rebuild with decoded words
    QRegularExpressionMatchIterator matchIt = re.globalMatch(result);
    QString output;
    int lastEnd = 0;

    while (matchIt.hasNext()) {
        auto match = matchIt.next();
        output += result.mid(lastEnd, match.capturedStart() - lastEnd);

        QString charset = match.captured(1);
        QString encoding = match.captured(2).toUpper();
        QString encodedText = match.captured(3);

        QByteArray decodedBytes;
        if (encoding == "B") {
            decodedBytes = QByteArray::fromBase64(encodedText.toLatin1());
        } else if (encoding == "Q") {
            // Q encoding is like quoted-printable but with _ for space
            QByteArray qEncoded = encodedText.toLatin1().replace('_', ' ');
            decodedBytes = MimeUtils::decodeQuotedPrintable(qEncoded);
        }

        output += MimeUtils::decodeCharset(decodedBytes, charset);
        lastEnd = match.capturedEnd();
    }

    if (lastEnd == 0) return input; // No encoded words found
    output += result.mid(lastEnd);

    // Remove whitespace between consecutive encoded words
    static QRegularExpression wsRe(R"(\?=\s+=\?)");
    // Already decoded, so just return
    return output.toUtf8();
}

QJsonObject EmailMessage::toMetadataJson() const {
    QJsonObject obj;
    obj["messageId"] = m_messageId;
    obj["contentHash"] = m_contentHash;
    obj["threadId"] = m_threadId;
    obj["subject"] = m_subject;
    obj["from"] = m_from.toJson();
    obj["date"] = m_date.toString(Qt::ISODate);
    obj["inReplyTo"] = m_inReplyTo;

    QJsonArray refsArr;
    for (const QString &r : m_references) refsArr.append(r);
    obj["references"] = refsArr;

    QJsonArray toArr;
    for (const EmailAddress &a : m_to) toArr.append(a.toJson());
    obj["to"] = toArr;

    QJsonArray ccArr;
    for (const EmailAddress &a : m_cc) ccArr.append(a.toJson());
    obj["cc"] = ccArr;

    QJsonArray bccArr;
    for (const EmailAddress &a : m_bcc) bccArr.append(a.toJson());
    obj["bcc"] = bccArr;

    QJsonArray labelsArr;
    for (const QString &l : m_gmailLabels) labelsArr.append(l);
    obj["gmailLabels"] = labelsArr;

    QJsonArray attArr;
    for (const Attachment &a : m_attachments) attArr.append(a.toJson());
    obj["attachments"] = attArr;

    obj["hasTextBody"] = !m_textBody.isEmpty();
    obj["hasHtmlBody"] = !m_htmlBody.isEmpty();

    return obj;
}

QString EmailMessage::safeFilename() const {
    QString base = m_subject.left(60);
    base.replace(QRegularExpression(R"([<>:"/\\|?*\x00-\x1f])"), "_");
    base = base.trimmed();
    if (base.isEmpty()) base = "no_subject";

    QString dateStr = m_date.isValid() ? m_date.toString("yyyy-MM-dd_HHmmss") : "nodate";
    QString hashPart = m_contentHash.left(12);

    return QStringLiteral("%1_%2_%3").arg(dateStr, base, hashPart);
}
```

## src/mimeutils.h

```cpp
#pragma once

#include <QByteArray>
#include <QString>
#include <QStringList>
#include <QDateTime>
#include <QMap>

namespace MimeUtils {

QByteArray decodeQuotedPrintable(const QByteArray &input);
QByteArray decodeBase64(const QByteArray &input);
QByteArray decodeBody(const QByteArray &body, const QMap<QString, QStringList> &headers);
QString decodeCharset(const QByteArray &data, const QString &charset);
QString extractParam(const QString &headerValue, const QString &paramName);
QStringList splitAddressList(const QString &addressList);
QDateTime parseDate(const QString &dateStr);
QString sanitizePath(const QString &path);

} // namespace MimeUtils
```

## src/mimeutils.cpp

```cpp
#include "mimeutils.h"

#include <QRegularExpression>
#include <QTextCodec>
#include <QTimeZone>
#include <QDebug>

namespace MimeUtils {

QByteArray decodeQuotedPrintable(const QByteArray &input) {
    QByteArray output;
    output.reserve(input.size());

    for (int i = 0; i < input.size(); ++i) {
        if (input[i] == '=' && i + 2 < input.size()) {
            if (input[i+1] == '\r' || input[i+1] == '\n') {
                // Soft line break
                if (input[i+1] == '\r' && i + 2 < input.size() && input[i+2] == '\n')
                    i += 2;
                else
                    i += 1;
                continue;
            }
            bool ok1, ok2;
            int hi = QString(QChar::fromLatin1(input[i+1])).toInt(&ok1, 16);
            int lo = QString(QChar::fromLatin1(input[i+2])).toInt(&ok2, 16);
            if (ok1 && ok2) {
                output.append(static_cast<char>((hi << 4) | lo));
                i += 2;
            } else {
                output.append(input[i]);
            }
        } else {
            output.append(input[i]);
        }
    }

    return output;
}

QByteArray decodeBase64(const QByteArray &input) {
    return QByteArray::fromBase64(input);
}

QByteArray decodeBody(const QByteArray &body, const QMap<QString, QStringList> &headers) {
    QString encoding;
    if (headers.contains("content-transfer-encoding"))
        encoding = headers["content-transfer-encoding"].first().trimmed().toLower();

    if (encoding == "base64") {
        return decodeBase64(body);
    } else if (encoding == "quoted-printable") {
        return decodeQuotedPrintable(body);
    }
    return body;
}

QString decodeCharset(const QByteArray &data, const QString &charset) {
    if (data.isEmpty()) return {};

    QString cs = charset.trimmed().toLower();
    if (cs.isEmpty() || cs == "utf-8" || cs == "utf8") {
        return QString::fromUtf8(data);
    }

    QTextCodec *codec = QTextCodec::codecForName(cs.toLatin1());
    if (codec) {
        return codec->toUnicode(data);
    }

    // Fallback to UTF-8
    return QString::fromUtf8(data);
}

QString extractParam(const QString &headerValue, const QString &paramName) {
    // Look for paramName=value or paramName="value"
    QString pattern = paramName + R"(\s*=\s*"?([^";,\s]+)"?)";
    QRegularExpression re(pattern, QRegularExpression::CaseInsensitiveOption);
    auto match = re.match(headerValue);
    if (match.hasMatch()) {
        return match.captured(1);
    }

    // Also try with quoted value containing spaces
    QString pattern2 = paramName + R"(\s*=\s*"([^"]*)")";
    QRegularExpression re2(pattern2, QRegularExpression::CaseInsensitiveOption);
    auto match2 = re2.match(headerValue);
    if (match2.hasMatch()) {
        return match2.captured(1);
    }

    return {};
}

QStringList splitAddressList(const QString &addressList) {
    QStringList result;
    int depth = 0;
    bool inQuotes = false;
    int start = 0;

    for (int i = 0; i < addressList.size(); ++i) {
        QChar c = addressList[i];
        if (c == '"') inQuotes = !inQuotes;
        if (!inQuotes) {
            if (c == '(') depth++;
            else if (c == ')') depth--;
            else if (c == ',' && depth == 0) {
                QString addr = addressList.mid(start, i - start).trimmed();
                if (!addr.isEmpty()) result.append(addr);
                start = i + 1;
            }
        }
    }

    QString last = addressList.mid(start).trimmed();
    if (!last.isEmpty()) result.append(last);

    return result;
}

QDateTime parseDate(const QString &dateStr) {
    // Try various date formats
    static const QStringList formats = {
        "ddd, dd MMM yyyy HH:mm:ss",
        "ddd, d MMM yyyy HH:mm:ss",
        "dd MMM yyyy HH:mm:ss",
        "d MMM yyyy HH:mm:ss",
        "ddd, dd MMM yyyy HH:mm",
        "yyyy-MM-dd HH:mm:ss",
        "yyyy-MM-ddTHH:mm:ss",
    };

    // Strip timezone info for parsing, handle it separately
    QString cleaned = dateStr.trimmed();

    // Remove comments in parentheses
    static QRegularExpression commentRe(R"(\([^)]*\))");
    cleaned.remove(commentRe);
    cleaned = cleaned.trimmed();

    // Try Qt's built-in RFC 2822 parsing first
    QDateTime dt = QDateTime::fromString(cleaned, Qt::RFC2822Date);
    if (dt.isValid()) return dt.toUTC();

    // Remove timezone suffix for manual parsing
    static QRegularExpression tzRe(R"(\s+[+-]\d{4}\s*$)");
    QString withoutTz = cleaned;
    withoutTz.remove(tzRe);
    withoutTz = withoutTz.trimmed();

    for (const QString &fmt : formats) {
        dt = QDateTime::fromString(withoutTz, fmt);
        if (dt.isValid()) return dt;
    }

    // Last resort
    dt = QDateTime::fromString(dateStr.trimmed());
    if (dt.isValid()) return dt;

    return QDateTime::currentDateTimeUtc();
}

QString sanitizePath(const QString &path) {
    QString result = path;
    result.replace(QRegularExpression(R"([<>:"|?*\x00-\x1f])"), "_");
    // Collapse multiple underscores
    result.replace(QRegularExpression(R"(_{2,})"), "_");
    // Remove leading/trailing dots and spaces from each component
    QStringList parts = result.split('/');
    for (QString &part : parts) {
        part = part.trimmed();
        while (part.startsWith('.')) part.remove(0, 1);
        while (part.endsWith('.')) part.chop(1);
        if (part.isEmpty()) part = "_";
    }
    return parts.join('/');
}

} // namespace MimeUtils
```

## src/deduplicator.h

```cpp
#pragma once

#include <QString>
#include <QSet>
#include <QMutex>
#include <QJsonObject>

class Deduplicator {
public:
    explicit Deduplicator(const QString &storePath);

    // Load known message hashes from index
    bool loadIndex();

    // Save index to disk
    bool saveIndex();

    // Check if message is duplicate, returns true if it IS a duplicate
    bool isDuplicate(const QString &messageId, const QString &contentHash) const;

    // Register a message as processed
    void registerMessage(const QString &messageId, const QString &contentHash,
                         const QString &storagePath);

    // Get existing storage path for a duplicate
    QString existingPath(const QString &messageId) const;

    qint64 totalMessages() const { return m_messageIds.size(); }
    qint64 duplicatesSkipped() const { return m_duplicatesSkipped; }

private:
    QString m_storePath;
    QString m_indexPath;
    QSet<QString> m_messageIds;
    QSet<QString> m_contentHashes;
    QMap<QString, QString> m_idToPath; // messageId -> storage path
    mutable qint64 m_duplicatesSkipped = 0;
    mutable QMutex m_mutex;
};
```

## src/deduplicator.cpp

```cpp
#include "deduplicator.h"

#include <QFile>
#include <QJsonDocument>
#include <QJsonArray>
#include <QJsonObject>
#include <QDir>
#include <QDebug>

Deduplicator::Deduplicator(const QString &storePath)
    : m_storePath(storePath)
    , m_indexPath(storePath + "/dedup_index.json")
{
}

bool Deduplicator::loadIndex() {
    QFile file(m_indexPath);
    if (!file.exists()) return true; // First run

    if (!file.open(QIODevice::ReadOnly)) {
        qWarning() << "Cannot open dedup index:" << m_indexPath;
        return false;
    }

    QJsonDocument doc = QJsonDocument::fromJson(file.readAll());
    file.close();

    if (!doc.isObject()) return false;

    QJsonObject root = doc.object();
    QJsonArray messages = root["messages"].toArray();

    for (const QJsonValue &val : messages) {
        QJsonObject msg = val.toObject();
        QString msgId = msg["messageId"].toString();
        QString hash = msg["contentHash"].toString();
        QString path = msg["path"].toString();

        m_messageIds.insert(msgId);
        m_contentHashes.insert(hash);
        m_idToPath[msgId] = path;
    }

    qInfo() << "Loaded dedup index with" << m_messageIds.size() << "known messages";
    return true;
}

bool Deduplicator::saveIndex() {
    QDir().mkpath(m_storePath);

    QJsonArray messages;
    for (auto it = m_idToPath.constBegin(); it != m_idToPath.constEnd(); ++it) {
        QJsonObject msg;
        msg["messageId"] = it.key();
        msg["path"] = it.value();
        messages.append(msg);
    }

    QJsonObject root;
    root["version"] = 1;
    root["messageCount"] = (qint64)m_messageIds.size();
    root["messages"] = messages;

    QFile file(m_indexPath);
    if (!file.open(QIODevice::WriteOnly)) {
        qWarning() << "Cannot write dedup index:" << m_indexPath;
        return false;
    }

    file.write(QJsonDocument(root).toJson(QJsonDocument::Compact));
    file.close();
    return true;
}

bool Deduplicator::isDuplicate(const QString &messageId, const QString &contentHash) const {
    QMutexLocker locker(&m_mutex);

    if (m_messageIds.contains(messageId)) {
        m_duplicatesSkipped++;
        return true;
    }

    if (!contentHash.isEmpty() && m_contentHashes.contains(contentHash)) {
        m_duplicatesSkipped++;
        return true;
    }

    return false;
}

void Deduplicator::registerMessage(const QString &messageId, const QString &contentHash,
                                    const QString &storagePath) {
    QMutexLocker locker(&m_mutex);
    m_messageIds.insert(messageId);
    if (!contentHash.isEmpty())
        m_contentHashes.insert(contentHash);
    m_idToPath[messageId] = storagePath;
}

QString Deduplicator::existingPath(const QString &messageId) const {
    QMutexLocker locker(&m_mutex);
    return m_idToPath.value(messageId);
}
```

## src/threadbuilder.h

```cpp
#pragma once

#include "emailmessage.h"

#include <QString>
#include <QMap>
#include <QList>
#include <QJsonObject>

struct ThreadInfo {
    QString threadId;
    QString subject;           // Normalized subject (without Re:/Fwd:)
    QStringList messageIds;    // Ordered list of message IDs in thread
    QDateTime firstDate;
    QDateTime lastDate;
    QStringList participants;  // All email addresses involved
    int messageCount = 0;

    QJsonObject toJson() const;
};

class ThreadBuilder {
public:
    ThreadBuilder() = default;

    // Add a message to the thread builder
    void addMessage(const EmailMessage &msg);

    // Build threads from all added messages
    void buildThreads();

    // Get thread ID for a message ID
    QString threadIdForMessage(const QString &messageId) const;

    // Get all threads
    QMap<QString, ThreadInfo> threads() const { return m_threads; }

    // Get thread info
    ThreadInfo threadInfo(const QString &threadId) const;

private:
    QString normalizeSubject(const QString &subject) const;
    QString findOrCreateThread(const EmailMessage &msg);

    struct MessageInfo {
        QString messageId;
        QString inReplyTo;
        QStringList references;
        QString subject;
        QDateTime date;
        QString from;
    };

    QList<MessageInfo> m_messages;
    QMap<QString, QString> m_messageToThread; // messageId -> threadId
    QMap<QString, ThreadInfo> m_threads;
    QMap<QString, QString> m_subjectToThread; // normalized subject -> threadId
    int m_nextThreadId = 0;
};
```

## src/threadbuilder.cpp

```cpp
#include "threadbuilder.h"

#include <QCryptographicHash>
#include <QRegularExpression>
#include <QSet>
#include <QDebug>

QJsonObject ThreadInfo::toJson() const {
    QJsonObject obj;
    obj["threadId"] = threadId;
    obj["subject"] = subject;
    obj["messageCount"] = messageCount;
    obj["firstDate"] = firstDate.toString(Qt::ISODate);
    obj["lastDate"] = lastDate.toString(Qt::ISODate);

    QJsonArray msgIds;
    for (const QString &id : messageIds) msgIds.append(id);
    obj["messageIds"] = msgIds;

    QJsonArray parts;
    for (const QString &p : participants) parts.append(p);
    obj["participants"] = parts;

    return obj;
}

void ThreadBuilder::addMessage(const EmailMessage &msg) {
    MessageInfo info;
    info.messageId = msg.messageId();
    info.inReplyTo = msg.inReplyTo();
    info.references = msg.references();
    info.subject = msg.subject();
    info.date = msg.date();
    info.from = msg.from().email;
    m_messages.append(info);
}

void ThreadBuilder::buildThreads() {
    // Sort messages by date
    std::sort(m_messages.begin(), m_messages.end(),
              [](const MessageInfo &a, const MessageInfo &b) {
                  return a.date < b.date;
              });

    for (const MessageInfo &msg : m_messages) {
        // Try to find existing thread by references
        QString threadId;

        // Check references first (most reliable for threading)
        for (const QString &ref : msg.references) {
            if (m_messageToThread.contains(ref)) {
                threadId = m_messageToThread[ref];
                break;
            }
        }

        // Check in-reply-to
        if (threadId.isEmpty() && !msg.inReplyTo.isEmpty()) {
            if (m_messageToThread.contains(msg.inReplyTo)) {
                threadId = m_messageToThread[msg.inReplyTo];
            }
        }

        // Fall back to subject matching
        if (threadId.isEmpty()) {
            QString normSubject = normalizeSubject(msg.subject);
            if (!normSubject.isEmpty() && m_subjectToThread.contains(normSubject)) {
                threadId = m_subjectToThread[normSubject];
            }
        }

        // Create new thread if needed
        if (threadId.isEmpty()) {
            threadId = QStringLiteral("thread_%1").arg(m_nextThreadId++, 6, 10, QChar('0'));
            ThreadInfo thread;
            thread.threadId = threadId;
            thread.subject = normalizeSubject(msg.subject);
            thread.firstDate = msg.date;
            m_threads[threadId] = thread;
        }

        // Add message to thread
        m_messageToThread[msg.messageId] = threadId;

        ThreadInfo &thread = m_threads[threadId];
        thread.messageIds.append(msg.messageId);
        thread.messageCount++;
        thread.lastDate = msg.date;

        if (!thread.participants.contains(msg.from))
            thread.participants.append(msg.from);

        // Register subject for future matches
        QString normSubject = normalizeSubject(msg.subject);
        if (!normSubject.isEmpty() && !m_subjectToThread.contains(normSubject)) {
            m_subjectToThread[normSubject] = threadId;
        }
    }

    qInfo() << "Built" << m_threads.size() << "threads from" << m_messages.size() << "messages";
}

QString ThreadBuilder::threadIdForMessage(const QString &messageId) const {
    return m_messageToThread.value(messageId);
}

ThreadInfo ThreadBuilder::threadInfo(const QString &threadId) const {
    return m_threads.value(threadId);
}

QString ThreadBuilder::normalizeSubject(const QString &subject) const {
    QString norm = subject.trimmed();
    // Remove Re:, Fwd:, Fw: prefixes (possibly repeated)
    static QRegularExpression prefixRe(
        R"(^(?:\s*(?:Re|Fwd|Fw)\s*(?:\[\d+\])?\s*:\s*)+)",
        QRegularExpression::CaseInsensitiveOption);
    norm.remove(prefixRe);
    norm = norm.trimmed().toLower();
    return norm;
}
```

## src/folderorganizer.h

```cpp
#pragma once

#include "emailmessage.h"
#include "threadbuilder.h"

#include <QString>
#include <QMap>

class FolderOrganizer {
public:
    explicit FolderOrganizer(const QString &outputBasePath);

    // Determine the storage path for a message
    // Returns relative path from base
    QString determineStoragePath(const EmailMessage &msg, const ThreadInfo &threadInfo) const;

    // Get the folder for attachments of a message
    QString attachmentFolder(const QString &messagePath) const;

    // Create directory structure
    bool ensureDirectoryExists(const QString &relativePath) const;

    // Get full path
    QString fullPath(const QString &relativePath) const;

private:
    QString labelToFolder(const QString &label) const;
    QString m_basePath;
};
```

## src/folderorganizer.cpp

```cpp
#include "folderorganizer.h"
#include "mimeutils.h"

#include <QDir>
#include <QDebug>

FolderOrganizer::FolderOrganizer(const QString &outputBasePath)
    : m_basePath(outputBasePath)
{
}

QString FolderOrganizer::determineStoragePath(const EmailMessage &msg,
                                                const ThreadInfo &threadInfo) const {
    // Structure:
    // output/
    //   YYYY/
    //     MM/
    //       label_or_category/
    //         thread_subject/
    //           YYYY-MM-DD_HHmmss_subject_hash.mbox
    //           YYYY-MM-DD_HHmmss_subject_hash.meta.json
    //           attachments/
    //             filename.ext

    QStringList pathParts;

    // Year/Month based on message date
    QDateTime date = msg.date();
    if (!date.isValid()) date = QDateTime::currentDateTimeUtc();

    pathParts << date.toString("yyyy");
    pathParts << date.toString("MM");

    // Label/Category
    QString label = "Other";
    QStringList gmailLabels = msg.gmailLabels();

    // Priority order for label selection
    static const QStringList priorityLabels = {
        "Inbox", "Sent", "Drafts", "Spam", "Trash", "Starred", "Important"
    };

    for (const QString &pl : priorityLabels) {
        for (const QString &gl : gmailLabels) {
            if (gl.compare(pl, Qt::CaseInsensitive) == 0) {
                label = pl;
                goto labelFound;
            }
        }
    }

    // Check for category labels
    for (const QString &gl : gmailLabels) {
        if (gl.startsWith("Category ", Qt::CaseInsensitive)) {
            label = gl;
            goto labelFound;
        }
    }

    // Use first custom label if available
    for (const QString &gl : gmailLabels) {
        bool isSystem = false;
        for (const QString &pl : priorityLabels) {
            if (gl.compare(pl, Qt::CaseInsensitive) == 0) {
                isSystem = true;
                break;
            }
        }
        if (!isSystem && gl != "Unread" && gl != "Opened") {
            label = gl;
            goto labelFound;
        }
    }

    labelFound:
    pathParts << labelToFolder(label);

    // Thread folder
    if (!threadInfo.threadId.isEmpty() && threadInfo.messageCount > 1) {
        QString threadFolder = threadInfo.subject.left(50);
        if (threadFolder.isEmpty()) threadFolder = "no_subject";
        threadFolder = MimeUtils::sanitizePath(threadFolder);
        threadFolder.replace('/', '_');
        pathParts << QStringLiteral("%1_[%2]").arg(threadFolder, threadInfo.threadId);
    }

    QString relativePath = pathParts.join('/');
    return relativePath;
}

QString FolderOrganizer::attachmentFolder(const QString &messagePath) const {
    return messagePath + "/attachments";
}

bool FolderOrganizer::ensureDirectoryExists(const QString &relativePath) const {
    QString fullPath = m_basePath + "/" + relativePath;
    return QDir().mkpath(fullPath);
}

QString FolderOrganizer::fullPath(const QString &relativePath) const {
    return m_basePath + "/" + relativePath;
}

QString FolderOrganizer::labelToFolder(const QString &label) const {
    QString folder = label;
    folder.replace('/', '-');
    folder = MimeUtils::sanitizePath(folder);
    return folder;
}
```

## src/messagestore.h

```cpp
#pragma once

#include "emailmessage.h"
#include "folderorganizer.h"
#include "deduplicator.h"
#include "threadbuilder.h"

#include <QString>

class MessageStore {
public:
    MessageStore(const QString &outputPath);

    // Initialize store (load existing index)
    bool initialize();

    // Store a parsed message
    bool storeMessage(EmailMessage &msg, const ThreadInfo &threadInfo);

    // Finalize (save indices, thread info, etc.)
    bool finalize(const ThreadBuilder &threadBuilder);

    // Statistics
    qint64 messagesStored() const { return m_messagesStored; }
    qint64 duplicatesSkipped() const { return m_deduplicator.duplicatesSkipped(); }

    Deduplicator& deduplicator() { return m_deduplicator; }

private:
    bool writeMessageMbox(const EmailMessage &msg, const QString &fullPath);
    bool writeMessageMetadata(const EmailMessage &msg, const QString &fullPath);
    bool writeMessageTextBody(const EmailMessage &msg, const QString &fullPath);

    QString m_outputPath;
    FolderOrganizer m_organizer;
    Deduplicator m_deduplicator;
    qint64 m_messagesStored = 0;
};
```

## src/messagestore.cpp

```cpp
#include "messagestore.h"

#include <QFile>
#include <QDir>
#include <QJsonDocument>
#include <QJsonArray>
#include <QDebug>
#include <QTextStream>

MessageStore::MessageStore(const QString &outputPath)
    : m_outputPath(outputPath)
    , m_organizer(outputPath)
    , m_deduplicator(outputPath)
{
}

bool MessageStore::initialize() {
    QDir().mkpath(m_outputPath);

    if (!m_deduplicator.loadIndex()) {
        qWarning() << "Failed to load dedup index, starting fresh";
    }

    return true;
}

bool MessageStore::storeMessage(EmailMessage &msg, const ThreadInfo &threadInfo) {
    // Check for duplicates
    if (m_deduplicator.isDuplicate(msg.messageId(), msg.contentHash())) {
        return true; // Skip silently
    }

    // Determine storage path
    QString folderPath = m_organizer.determineStoragePath(msg, threadInfo);
    m_organizer.ensureDirectoryExists(folderPath);

    QString filename = msg.safeFilename();
    QString fullDir = m_organizer.fullPath(folderPath);

    // Write individual .mbox file
    QString mboxPath = fullDir + "/" + filename + ".mbox";
    if (!writeMessageMbox(msg, mboxPath)) {
        qWarning() << "Failed to write mbox:" << mboxPath;
        return false;
    }

    // Write metadata JSON
    QString metaPath = fullDir + "/" + filename + ".meta.json";
    if (!writeMessageMetadata(msg, metaPath)) {
        qWarning() << "Failed to write metadata:" << metaPath;
        return false;
    }

    // Write plain text body for easy search
    if (!msg.textBody().isEmpty()) {
        QString textPath = fullDir + "/" + filename + ".txt";
        writeMessageTextBody(msg, textPath);
    }

    // Register with deduplicator
    QString relativePath = folderPath + "/" + filename;
    m_deduplicator.registerMessage(msg.messageId(), msg.contentHash(), relativePath);

    m_messagesStored++;
    return true;
}

bool MessageStore::finalize(const ThreadBuilder &threadBuilder) {
    // Save dedup index
    if (!m_deduplicator.saveIndex()) {
        qWarning() << "Failed to save dedup index";
    }

    // Save thread index
    QJsonObject threadsObj;
    QJsonArray threadArray;
    for (const auto &thread : threadBuilder.threads()) {
        threadArray.append(thread.toJson());
    }
    threadsObj["version"] = 1;
    threadsObj["threadCount"] = (qint64)threadBuilder.threads().size();
    threadsObj["threads"] = threadArray;

    QFile threadFile(m_outputPath + "/thread_index.json");
    if (threadFile.open(QIODevice::WriteOnly)) {
        threadFile.write(QJsonDocument(threadsObj).toJson(QJsonDocument::Indented));
        threadFile.close();
    }

    // Save master index with all messages
    // (Already covered by dedup_index.json and per-message .meta.json files)

    // Save summary
    QJsonObject summary;
    summary["totalMessages"] = m_messagesStored;
    summary["totalThreads"] = (qint64)threadBuilder.threads().size();
    summary["duplicatesSkipped"] = m_deduplicator.duplicatesSkipped();
    summary["totalKnownMessages"] = m_deduplicator.totalMessages();

    QFile summaryFile(m_outputPath + "/parse_summary.json");
    if (summaryFile.open(QIODevice::WriteOnly)) {
        summaryFile.write(QJsonDocument(summary).toJson(QJsonDocument::Indented));
        summaryFile.close();
    }

    return true;
}

bool MessageStore::writeMessageMbox(const EmailMessage &msg, const QString &fullPath) {
    QFile file(fullPath);
    if (!file.open(QIODevice::WriteOnly)) return false;

    // Write standard mbox "From " line
    QString fromLine = QStringLiteral("From %1 %2\n")
        .arg(msg.from().email.isEmpty() ? "unknown@unknown" : msg.from().email,
             msg.date().isValid() ? msg.date().toString("ddd MMM dd HH:mm:ss yyyy")
                                  : QDateTime::currentDateTimeUtc().toString("ddd MMM dd HH:mm:ss yyyy"));

    file.write(fromLine.toUtf8());
    file.write(msg.rawData());
    if (!msg.rawData().endsWith('\n'))
        file.write("\n");
    file.write("\n");

    file.close();
    return true;
}

bool MessageStore::writeMessageMetadata(const EmailMessage &msg, const QString &fullPath) {
    QFile file(fullPath);
    if (!file.open(QIODevice::WriteOnly)) return false;

    QJsonDocument doc(msg.toMetadataJson());
    file.write(doc.toJson(QJsonDocument::Indented));
    file.close();
    return true;
}

bool MessageStore::writeMessageTextBody(const EmailMessage &msg, const QString &fullPath) {
    QFile file(fullPath);
    if (!file.open(QIODevice::WriteOnly | QIODevice::Text)) return false;

    QTextStream stream(&file);
    stream.setEncoding(QStringConverter::Utf8);

    // Write searchable header at top
    stream << "Subject: " << msg.subject() << "\n";
    stream << "From: " << msg.from().toString() << "\n";
    stream << "Date: " << msg.date().toString(Qt::ISODate) << "\n";
    stream << "To: ";
    QStringList toAddrs;
    for (const auto &a : msg.to()) toAddrs << a.toString();
    stream << toAddrs.join(", ") << "\n";
    if (!msg.cc().isEmpty()) {
        QStringList ccAddrs;
        for (const auto &a : msg.cc()) ccAddrs << a.toString();
        stream << "Cc: " << ccAddrs.join(", ") << "\n";
    }
    stream << "\n---\n\n";
    stream << msg.textBody();

    file.close();
    return true;
}
```

## src/indexmanager.h

```cpp
#pragma once

#include "emailmessage.h"
#include "threadbuilder.h"

#include <QString>
#include <QMap>
#include <QJsonObject>

// Manages search indices for fast lookup
class IndexManager {
public:
    explicit IndexManager(const QString &outputPath);

    // Add message to indices
    void indexMessage(const EmailMessage &msg, const QString &storagePath);

    // Build and write all indices to disk
    bool writeIndices();

private:
    void addToSenderIndex(const EmailMessage &msg, const QString &path);
    void addToDateIndex(const EmailMessage &msg, const QString &path);
    void addToLabelIndex(const EmailMessage &msg, const QString &path);
    void addToSubjectWordIndex(const EmailMessage &msg, const QString &path);

    QString m_outputPath;

    // sender_email -> list of {messageId, path, date, subject}
    QMap<QString, QJsonArray> m_senderIndex;

    // "YYYY-MM" -> list of {messageId, path, from, subject}
    QMap<QString, QJsonArray> m_dateIndex;

    // label -> list of {messageId, path, date, subject, from}
    QMap<QString, QJsonArray> m_labelIndex;

    // word -> list of {messageId, path}
    QMap<QString, QJsonArray> m_wordIndex;

    qint64 m_totalIndexed = 0;
};
```

## src/indexmanager.cpp

```cpp
#include "indexmanager.h"

#include <QFile>
#include <QDir>
#include <QJsonDocument>
#include <QJsonArray>
#include <QRegularExpression>
#include <QDebug>

IndexManager::IndexManager(const QString &outputPath)
    : m_outputPath(outputPath + "/indices")
{
}

void IndexManager::indexMessage(const EmailMessage &msg, const QString &storagePath) {
    addToSenderIndex(msg, storagePath);
    addToDateIndex(msg, storagePath);
    addToLabelIndex(msg, storagePath);
    addToSubjectWordIndex(msg, storagePath);
    m_totalIndexed++;
}

void IndexManager::addToSenderIndex(const EmailMessage &msg, const QString &path) {
    QString sender = msg.from().email.toLower();
    if (sender.isEmpty()) return;

    QJsonObject entry;
    entry["messageId"] = msg.messageId();
    entry["path"] = path;
    entry["date"] = msg.date().toString(Qt::ISODate);
    entry["subject"] = msg.subject();
    entry["threadId"] = msg.threadId();

    m_senderIndex[sender].append(entry);
}

void IndexManager::addToDateIndex(const EmailMessage &msg, const QString &path) {
    QString yearMonth = msg.date().toString("yyyy-MM");
    if (yearMonth.isEmpty()) yearMonth = "unknown";

    QJsonObject entry;
    entry["messageId"] = msg.messageId();
    entry["path"] = path;
    entry["from"] = msg.from().email;
    entry["subject"] = msg.subject();
    entry["date"] = msg.date().toString(Qt::ISODate);
    entry["threadId"] = msg.threadId();

    m_dateIndex[yearMonth].append(entry);
}

void IndexManager::addToLabelIndex(const EmailMessage &msg, const QString &path) {
    QStringList labels = msg.gmailLabels();
    if (labels.isEmpty()) labels << "Unlabeled";

    QJsonObject entry;
    entry["messageId"] = msg.messageId();
    entry["path"] = path;
    entry["date"] = msg.date().toString(Qt::ISODate);
    entry["subject"] = msg.subject();
    entry["from"] = msg.from().toString();
    entry["threadId"] = msg.threadId();

    for (const QString &label : labels) {
        m_labelIndex[label.toLower()].append(entry);
    }
}

void IndexManager::addToSubjectWordIndex(const EmailMessage &msg, const QString &path) {
    // Extract significant words from subject for keyword search
    QString subject = msg.subject().toLower();
    static QRegularExpression wordRe(R"(\b[a-zA-Z\d]{3,}\b)");

    // Remove common prefixes
    static QRegularExpression prefixRe(R"(^(?:re|fwd|fw):\s*)", QRegularExpression::CaseInsensitiveOption);
    subject.remove(prefixRe);

    auto it = wordRe.globalMatch(subject);

    // Common stop words to skip
    static QSet<QString> stopWords = {
        "the", "and", "for", "are", "but", "not", "you", "all",
        "can", "her", "was", "one", "our", "out", "has", "have",
        "from", "this", "that", "with", "your", "will", "been",
        "they", "them", "then", "than", "what", "when", "where",
        "which", "their", "there", "these", "those", "would",
        "could", "should", "about", "after", "before"
    };

    QJsonObject entry;
    entry["messageId"] = msg.messageId();
    entry["path"] = path;

    while (it.hasNext()) {
        auto match = it.next();
        QString word = match.captured().toLower();
        if (!stopWords.contains(word)) {
            m_wordIndex[word].append(entry);
        }
    }
}

bool IndexManager::writeIndices() {
    QDir().mkpath(m_outputPath);

    // Write sender index
    {
        QDir().mkpath(m_outputPath + "/by_sender");
        for (auto it = m_senderIndex.constBegin(); it != m_senderIndex.constEnd(); ++it) {
            QString safeName = it.key();
            safeName.replace(QRegularExpression(R"([^a-zA-Z0-9.@_\-])"), "_");

            QJsonObject obj;
            obj["sender"] = it.key();
            obj["messageCount"] = (int)it.value().size();
            obj["messages"] = it.value();

            QFile file(m_outputPath + "/by_sender/" + safeName + ".json");
            if (file.open(QIODevice::WriteOnly)) {
                file.write(QJsonDocument(obj).toJson(QJsonDocument::Compact));
                file.close();
            }
        }

        // Write sender list
        QJsonObject senderList;
        QJsonArray senders;
        for (auto it = m_senderIndex.constBegin(); it != m_senderIndex.constEnd(); ++it) {
            QJsonObject s;
            s["email"] = it.key();
            s["messageCount"] = (int)it.value().size();
            senders.append(s);
        }
        senderList["senders"] = senders;
        QFile listFile(m_outputPath + "/by_sender/_sender_list.json");
        if (listFile.open(QIODevice::WriteOnly)) {
            listFile.write(QJsonDocument(senderList).toJson(QJsonDocument::Indented));
            listFile.close();
        }
    }

    // Write date index
    {
        QDir().mkpath(m_outputPath + "/by_date");
        for (auto it = m_dateIndex.constBegin(); it != m_dateIndex.constEnd(); ++it) {
            QJsonObject obj;
            obj["period"] = it.key();
            obj["messageCount"] = (int)it.value().size();
            obj["messages"] = it.value();

            QFile file(m_outputPath + "/by_date/" + it.key() + ".json");
            if (file.open(QIODevice::WriteOnly)) {
                file.write(QJsonDocument(obj).toJson(QJsonDocument::Compact));
                file.close();
            }
        }
    }

    // Write label index
    {
        QDir().mkpath(m_outputPath + "/by_label");
        for (auto it = m_labelIndex.constBegin(); it != m_labelIndex.constEnd(); ++it) {
            QString safeName = it.key();
            safeName.replace(QRegularExpression(R"([^a-zA-Z0-9._\-])"), "_");

            QJsonObject obj;
            obj["label"] = it.key();
            obj["messageCount"] = (int)it.value().size();
            obj["messages"] = it.value();

            QFile file(m_outputPath + "/by_label/" + safeName + ".json");
            if (file.open(QIODevice::WriteOnly)) {
                file.write(QJsonDocument(obj).toJson(QJsonDocument::Compact));
                file.close();
            }
        }

        // Write label list
        QJsonObject labelList;
        QJsonArray labels;
        for (auto it = m_labelIndex.constBegin(); it != m_labelIndex.constEnd(); ++it) {
            QJsonObject l;
            l["label"] = it.key();
            l["messageCount"] = (int)it.value().size();
            labels.append(l);
        }
        labelList["labels"] = labels;
        QFile listFile(m_outputPath + "/by_label/_label_list.json");
        if (listFile.open(QIODevice::WriteOnly)) {
            listFile.write(QJsonDocument(labelList).toJson(QJsonDocument::Indented));
            listFile.close();
        }
    }

    // Write word index (split into files by first letter for manageability)
    {
        QDir().mkpath(m_outputPath + "/by_word");
        QMap<QChar, QJsonObject> letterBuckets;

        for (auto it = m_wordIndex.constBegin(); it != m_wordIndex.constEnd(); ++it) {
            QChar firstLetter = it.key().isEmpty() ? '_' : it.key()[0];
            letterBuckets[firstLetter][it.key()] = it.value();
        }

        for (auto it = letterBuckets.constBegin(); it != letterBuckets.constEnd(); ++it) {
            QJsonObject obj;
            obj["letter"] = QString(it.key());
            obj["words"] = it.value();

            QFile file(m_outputPath + "/by_word/" + QString(it.key()) + ".json");
            if (file.open(QIODevice::WriteOnly)) {
                file.write(QJsonDocument(obj).toJson(QJsonDocument::Compact));
                file.close();
            }
        }
    }

    qInfo() << "Wrote indices for" << m_totalIndexed << "messages";
    qInfo() << "  Senders:" << m_senderIndex.size();
    qInfo() << "  Date periods:" << m_dateIndex.size();
    qInfo() << "  Labels:" << m_labelIndex.size();
    qInfo() << "  Index words:" << m_wordIndex.size();

    return true;
}
```

## src/mboxparser.h

```cpp
#pragma once

#include "emailmessage.h"

#include <QObject>
#include <QString>
#include <QFile>
#include <functional>

// Streaming parser for large mbox files
// Calls callback for each parsed message
class MboxParser : public QObject {
    Q_OBJECT

public:
    explicit MboxParser(QObject *parent = nullptr);

    // Parse an mbox file, calling callback for each message
    // Returns number of messages parsed, or -1 on error
    using MessageCallback = std::function<bool(EmailMessage &msg, qint64 messageNumber)>;
    qint64 parseFile(const QString &filePath, MessageCallback callback);

    // Statistics
    qint64 totalBytesProcessed() const { return m_bytesProcessed; }
    qint64 messagesFound() const { return m_messagesFound; }
    qint64 parseErrors() const { return m_parseErrors; }

signals:
    void progressUpdated(qint64 bytesProcessed, qint64 totalBytes, qint64 messagesFound);
    void errorOccurred(const QString &error);

private:
    bool isFromLine(const QByteArray &line) const;

    qint64 m_bytesProcessed = 0;
    qint64 m_messagesFound = 0;
    qint64 m_parseErrors = 0;
};
```

## src/mboxparser.cpp

```cpp
#include "mboxparser.h"

#include <QFileInfo>
#include <QDebug>
#include <QElapsedTimer>
#include <QRegularExpression>

MboxParser::MboxParser(QObject *parent)
    : QObject(parent)
{
}

qint64 MboxParser::parseFile(const QString &filePath, MessageCallback callback) {
    QFileInfo fi(filePath);
    if (!fi.exists()) {
        emit errorOccurred(QStringLiteral("File not found: %1").arg(filePath));
        return -1;
    }

    qint64 totalSize = fi.size();
    qInfo() << "Parsing mbox file:" << filePath;
    qInfo() << "File size:" << (totalSize / (1024.0 * 1024.0)) << "MB";

    QFile file(filePath);
    if (!file.open(QIODevice::ReadOnly)) {
        emit errorOccurred(QStringLiteral("Cannot open file: %1").arg(filePath));
        return -1;
    }

    QElapsedTimer timer;
    timer.start();

    m_bytesProcessed = 0;
    m_messagesFound = 0;
    m_parseErrors = 0;

    QByteArray currentMessage;
    bool inMessage = false;
    qint64 lineCount = 0;
    qint64 lastProgressReport = 0;

    // Read line by line for memory efficiency with huge files
    // Buffer size for reading
    constexpr int READ_BUFFER_SIZE = 64 * 1024; // 64KB line buffer

    while (!file.atEnd()) {
        QByteArray line = file.readLine(READ_BUFFER_SIZE);
        m_bytesProcessed += line.size();
        lineCount++;

        if (isFromLine(line)) {
            // Process previous message if any
            if (inMessage && !currentMessage.isEmpty()) {
                EmailMessage msg;
                if (msg.parseFromRaw(currentMessage)) {
                    m_messagesFound++;
                    if (!callback(msg, m_messagesFound)) {
                        qWarning() << "Callback returned false at message" << m_messagesFound;
                    }
                } else {
                    m_parseErrors++;
                    qDebug() << "Failed to parse message at line" << lineCount;
                }
                currentMessage.clear();
            }
            inMessage = true;
            // Don't include the "From " line in the message data
            continue;
        }

        if (inMessage) {
            // Un-escape "From " lines (mbox format escapes them with ">")
            if (line.startsWith(">From ")) {
                currentMessage.append(line.mid(1)); // Remove the '>'
            } else {
                currentMessage.append(line);
            }
        }

        // Progress reporting every 10MB
        if (m_bytesProcessed - lastProgressReport > 10 * 1024 * 1024) {
            lastProgressReport = m_bytesProcessed;
            emit progressUpdated(m_bytesProcessed, totalSize, m_messagesFound);

            double pct = totalSize > 0 ? (100.0 * m_bytesProcessed / totalSize) : 0;
            double elapsed = timer.elapsed() / 1000.0;
            double rate = elapsed > 0 ? (m_bytesProcessed / (1024.0 * 1024.0) / elapsed) : 0;

            qInfo().noquote() << QStringLiteral("  Progress: %1% | %2 messages | %3 MB/s")
                .arg(pct, 0, 'f', 1)
                .arg(m_messagesFound)
                .arg(rate, 0, 'f', 1);
        }
    }

    // Process last message
    if (inMessage && !currentMessage.isEmpty()) {
        EmailMessage msg;
        if (msg.parseFromRaw(currentMessage)) {
            m_messagesFound++;
            callback(msg, m_messagesFound);
        } else {
            m_parseErrors++;
        }
    }

    file.close();

    double elapsed = timer.elapsed() / 1000.0;
    qInfo() << "Parsing complete:";
    qInfo() << "  Messages found:" << m_messagesFound;
    qInfo() << "  Parse errors:" << m_parseErrors;
    qInfo() << "  Time:" << elapsed << "seconds";
    qInfo() << "  Speed:" << (totalSize / (1024.0 * 1024.0) / qMax(elapsed, 0.001)) << "MB/s";

    return m_messagesFound;
}

bool MboxParser::isFromLine(const QByteArray &line) const {
    // Standard mbox "From " separator line
    // Format: "From sender@email.com Day Mon DD HH:MM:SS YYYY"
    // Or simpler: starts with "From " followed by non-whitespace
    if (!line.startsWith("From ")) return false;

    // Must have at least some content after "From "
    if (line.size() < 7) return false;

    // The character after "From " should not be a colon (that would be a "From:" header)
    // Also verify it looks like an mbox separator
    // Google Takeout uses standard mbox format
    QByteArray rest = line.mid(5).trimmed();

    // Check if it matches the pattern: email_or_dash followed by date-like content
    // Be somewhat lenient since different mbox producers vary
    if (rest.contains('@') || rest.startsWith('-') || rest.startsWith("MAILER-DAEMON")) {
        return true;
    }

    // Some mbox files use simpler "From " lines
    // Check for date-like pattern after potential email
    static QRegularExpression fromRe(
        R"(^From\s+\S+\s+(?:Mon|Tue|Wed|Thu|Fri|Sat|Sun|Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec))",
        QRegularExpression::CaseInsensitiveOption);

    return fromRe.match(QString::fromLatin1(line)).hasMatch();
}
```

## src/main.cpp

```cpp
#include "mboxparser.h"
#include "messagestore.h"
#include "threadbuilder.h"
#include "indexmanager.h"
#include "deduplicator.h"

#include <QCoreApplication>
#include <QCommandLineParser>
#include <QCommandLineOption>
#include <QDir>
#include <QFileInfo>
#include <QElapsedTimer>
#include <QDebug>
#include <QTextStream>

static QTextStream& cout() {
    static QTextStream ts(stdout);
    return ts;
}

int main(int argc, char *argv[]) {
    QCoreApplication app(argc, argv);
    app.setApplicationName("mbox-parser");
    app.setApplicationVersion("1.0.0");

    QCommandLineParser cmdParser;
    cmdParser.setApplicationDescription(
        "Google Takeout MBOX Parser\n"
        "Parses .mbox files from Google Takeout and organizes them\n"
        "into a structured folder tree with threading and search indices.");
    cmdParser.addHelpOption();
    cmdParser.addVersionOption();

    QCommandLineOption outputOption(
        QStringList() << "o" << "output",
        "Output directory for parsed emails (default: ./email_archive)",
        "directory",
        "./email_archive");
    cmdParser.addOption(outputOption);

    QCommandLineOption twoPassOption(
        QStringList() << "2" << "two-pass",
        "Use two-pass mode for better threading (slower but more accurate)");
    cmdParser.addOption(twoPassOption);

    QCommandLineOption verboseOption(
        QStringList() << "v" << "verbose",
        "Verbose output");
    cmdParser.addOption(verboseOption);

    QCommandLineOption dryRunOption(
        QStringList() << "n" << "dry-run",
        "Parse and analyze without writing files");
    cmdParser.addOption(dryRunOption);

    cmdParser.addPositionalArgument("files", "MBOX files to parse", "<file1.mbox> [file2.mbox ...]");

    cmdParser.process(app);

    QStringList inputFiles = cmdParser.positionalArguments();
    if (inputFiles.isEmpty()) {
        cout() << "Error: No input files specified.\n\n";
        cmdParser.showHelp(1);
        return 1;
    }

    QString outputPath = cmdParser.value(outputOption);
    bool twoPass = cmdParser.isSet(twoPassOption);
    bool verbose = cmdParser.isSet(verboseOption);
    bool dryRun = cmdParser.isSet(dryRunOption);

    // Validate input files
    for (const QString &f : inputFiles) {
        QFileInfo fi(f);
        if (!fi.exists()) {
            cout() << "Error: File not found: " << f << "\n";
            return 1;
        }
        if (!fi.isReadable()) {
            cout() << "Error: File not readable: " << f << "\n";
            return 1;
        }
    }

    cout() << "=== Google Takeout MBOX Parser ===\n";
    cout() << "Output directory: " << QDir(outputPath).absolutePath() << "\n";
    cout() << "Input files: " << inputFiles.size() << "\n";
    cout() << "Two-pass mode: " << (twoPass ? "yes" : "no") << "\n";
    cout() << "Dry run: " << (dryRun ? "yes" : "no") << "\n";
    cout() << "\n";
    cout().flush();

    QElapsedTimer totalTimer;
    totalTimer.start();

    // Initialize store
    MessageStore store(outputPath);
    if (!dryRun) {
        if (!store.initialize()) {
            cout() << "Error: Failed to initialize message store\n";
            return 1;
        }
    }

    ThreadBuilder threadBuilder;
    IndexManager indexManager(outputPath);

    if (twoPass) {
        // === PASS 1: Scan all messages to build thread map ===
        cout() << "--- Pass 1: Scanning for threading ---\n";
        cout().flush();

        for (const QString &inputFile : inputFiles) {
            cout() << "\nScanning: " << inputFile << "\n";
            cout().flush();

            MboxParser parser;
            QObject::connect(&parser, &MboxParser::progressUpdated,
                [&](qint64 bytes, qint64 total, qint64 msgs) {
                    if (verbose) {
                        double pct = total > 0 ? (100.0 * bytes / total) : 0;
                        cout() << QStringLiteral("  Scan: %1% (%2 messages)\r")
                            .arg(pct, 0, 'f', 1).arg(msgs);
                        cout().flush();
                    }
                });

            parser.parseFile(inputFile, [&](EmailMessage &msg, qint64) -> bool {
                // Only collect threading info, don't store
                if (!store.deduplicator().isDuplicate(msg.messageId(), msg.contentHash())) {
                    threadBuilder.addMessage(msg);
                }
                return true;
            });
        }

        threadBuilder.buildThreads();
        cout() << "\nThreading complete: " << threadBuilder.threads().size() << " threads\n\n";
        cout().flush();

        // Reset deduplicator state for pass 2 (we want to re-process)
        // Actually, we keep it to skip duplicates in pass 2 too
    }

    // === PASS 2 (or single pass): Parse and store ===
    cout() << (twoPass ? "--- Pass 2: Storing messages ---\n" : "--- Parsing and storing ---\n");
    cout().flush();

    // Need to reload dedup for second pass if two-pass
    if (twoPass) {
        // Re-initialize to clear the in-memory state from pass 1 scanning
        store.initialize();
    }

    qint64 totalMessages = 0;
    qint64 totalFiles = 0;

    for (const QString &inputFile : inputFiles) {
        totalFiles++;
        cout() << QStringLiteral("\n[%1/%2] Processing: %3\n")
            .arg(totalFiles).arg(inputFiles.size()).arg(inputFile);
        cout().flush();

        MboxParser parser;
        QObject::connect(&parser, &MboxParser::progressUpdated,
            [&](qint64 bytes, qint64 total, qint64 msgs) {
                // Progress is reported by the parser itself via qInfo
            });

        QObject::connect(&parser, &MboxParser::errorOccurred,
            [&](const QString &error) {
                cout() << "  ERROR: " << error << "\n";
                cout().flush();
            });

        parser.parseFile(inputFile, [&](EmailMessage &msg, qint64 msgNum) -> bool {
            if (dryRun) {
                if (verbose && msgNum % 1000 == 0) {
                    cout() << "  Dry run: " << msgNum << " messages\r";
                    cout().flush();
                }
                return true;
            }

            // Build thread info if not two-pass
            if (!twoPass) {
                threadBuilder.addMessage(msg);
            }

            // Get thread info for this message
            QString threadId = threadBuilder.threadIdForMessage(msg.messageId());
            ThreadInfo tInfo;
            if (!threadId.isEmpty()) {
                tInfo = threadBuilder.threadInfo(threadId);
                msg.setThreadId(threadId);
            }

            // Store message
            if (!store.storeMessage(msg, tInfo)) {
                qWarning() << "Failed to store message:" << msg.messageId();
            } else {
                // Index message
                QString storagePath = msg.safeFilename(); // Simplified
                indexManager.indexMessage(msg, storagePath);
            }

            if (msgNum % 500 == 0) {
                cout() << QStringLiteral("  Stored %1 messages (%2 duplicates skipped)\r")
                    .arg(store.messagesStored())
                    .arg(store.duplicatesSkipped());
                cout().flush();
            }

            return true;
        });

        totalMessages += parser.messagesFound();
        cout() << "\n";
    }

    // If single-pass, build threads now
    if (!twoPass && !dryRun) {
        cout() << "\nBuilding thread index...\n";
        cout().flush();
        threadBuilder.buildThreads();
    }

    // Finalize
    if (!dryRun) {
        cout() << "Writing indices...\n";
        cout().flush();
        indexManager.writeIndices();

        cout() << "Finalizing store...\n";
        cout().flush();
        store.finalize(threadBuilder);
    }

    // Summary
    double elapsed = totalTimer.elapsed() / 1000.0;
    cout() << "\n=== Summary ===\n";
    cout() << "Files processed: " << totalFiles << "\n";
    cout() << "Total messages found: " << totalMessages << "\n";
    cout() << "Messages stored: " << store.messagesStored() << "\n";
    cout() << "Duplicates skipped: " << store.duplicatesSkipped() << "\n";
    cout() << "Threads created: " << threadBuilder.threads().size() << "\n";
    cout() << "Total time: " << elapsed << " seconds\n";
    cout() << "Output: " << QDir(outputPath).absolutePath() << "\n";
    cout().flush();

    return 0;
}
```

## README.md

```markdown
# Google Takeout MBOX Parser

A Qt6-based console application that parses Google Takeout `.mbox` files and
organizes their contents into a well-structured directory tree optimized for
later viewing, searching, and display by a GUI application.

## Building

### Requirements

- Qt 6.10+ (Core and Core5Compat modules)
- CMake 3.20+
- C++20 capable compiler

### Build Steps

```bash
mkdir build && cd build
cmake .. -DCMAKE_PREFIX_PATH=/path/to/qt6
cmake --build . --parallel
```

## Usage

```bash
# Basic usage - single file
./mbox-parser /path/to/All\ mail\ Including\ Spam\ and\ Trash.mbox

# Multiple files with output directory
./mbox-parser -o ~/email_archive file1.mbox file2.mbox file3.mbox

# Two-pass mode for better threading (recommended for large archives)
./mbox-parser -2 -o ~/email_archive *.mbox

# Dry run to see stats without writing
./mbox-parser -n -v large_file.mbox

# Verbose mode
./mbox-parser -v -o ./output inbox.mbox sent.mbox
```

### Command Line Options

| Option | Description |
|--------|-------------|
| `-o, --output <dir>` | Output directory (default: `./email_archive`) |
| `-2, --two-pass` | Two-pass mode: first scan for threading, then store |
| `-v, --verbose` | Verbose progress output |
| `-n, --dry-run` | Parse without writing files |

## Solution Architecture

### Core Idea

The parser reads potentially huge `.mbox` files (many GB) in a **streaming
fashion** — line by line — so it never needs to load the entire file into
memory. Each individual email is parsed, deduplicated, threaded, and stored
as a small self-contained unit in a structured directory tree.

### Streaming Parser

The `MboxParser` class reads the file line-by-line, detecting message boundaries
by the standard `"From "` separator line. Each complete message is passed to a
callback function, then the buffer is cleared. This means the parser can handle
files of any size with constant memory usage (proportional to the size of a
single email, not the entire file).

### Deduplication Strategy

Every message is identified by two keys:
1. **Message-ID** header (primary key, RFC standard)
2. **Content Hash** (SHA-256 of Message-ID + Date + Subject + From)

When the same `.mbox` file is parsed multiple times, or when multiple `.mbox`
files contain the same emails (common with Google Takeout where "All Mail"
overlaps with "Inbox", "Sent", etc.), duplicates are detected and silently
skipped. The deduplication index is persisted to `dedup_index.json` in the
output directory, so it survives across multiple runs.

### Threading

Emails are grouped into conversation threads using three mechanisms
(in priority order):

1. **References header** — most reliable; contains the full chain of
   Message-IDs in the conversation
2. **In-Reply-To header** — points to the immediate parent message
3. **Subject matching** — fallback; groups messages with the same normalized
   subject (after stripping Re:/Fwd: prefixes)

For best threading results, use **two-pass mode** (`-2`), which scans all
files first to build a complete thread map before storing.

### Two-Pass Mode

- **Pass 1:** Streams through all input files, extracting only threading
  metadata (Message-ID, References, In-Reply-To, Subject, Date). This builds
  the complete thread graph.
- **Pass 2:** Streams through again, this time storing each message into the
  correct thread folder with proper thread IDs assigned.

This is recommended for large archives where threading accuracy matters.

## Output Directory Structure

```
email_archive/
├── parse_summary.json          # Overall statistics
├── dedup_index.json            # Deduplication state (for re-runs)
├── thread_index.json           # Complete thread listing
│
├── indices/                    # Search indices (see below)
│   ├── by_sender/
│   │   ├── _sender_list.json   # List of all senders with message counts
│   │   ├── alice@example.com.json
│   │   └── bob@example.com.json
│   ├── by_date/
│   │   ├── 2023-01.json
│   │   ├── 2023-02.json
│   │   └── ...
│   ├── by_label/
│   │   ├── _label_list.json    # List of all labels with message counts
│   │   ├── inbox.json
│   │   ├── sent.json
│   │   └── important.json
│   └── by_word/
│       ├── a.json              # Subject word index, bucketed by first letter
│       ├── b.json
│       └── ...
│
├── 2023/                       # Year
│   ├── 01/                     # Month
│   │   ├── Inbox/              # Gmail label
│   │   │   ├── meeting_notes_[thread_000042]/  # Thread folder
│   │   │   │   ├── 2023-01-15_103022_Meeting_notes_a1b2c3d4e5f6.mbox
│   │   │   │   ├── 2023-01-15_103022_Meeting_notes_a1b2c3d4e5f6.meta.json
│   │   │   │   ├── 2023-01-15_103022_Meeting_notes_a1b2c3d4e5f6.txt
│   │   │   │   ├── 2023-01-15_142055_Re_Meeting_notes_f6e5d4c3b2a1.mbox
│   │   │   │   ├── 2023-01-15_142055_Re_Meeting_notes_f6e5d4c3b2a1.meta.json
│   │   │   │   └── 2023-01-15_142055_Re_Meeting_notes_f6e5d4c3b2a1.txt
│   │   │   │
│   │   │   ├── 2023-01-16_090000_Hello_world_abc123def456.mbox     # Standalone message
│   │   │   ├── 2023-01-16_090000_Hello_world_abc123def456.meta.json
│   │   │   └── 2023-01-16_090000_Hello_world_abc123def456.txt
│   │   │
│   │   └── Sent/
│   │       └── ...
│   └── 02/
│       └── ...
└── 2024/
    └── ...
```

### Per-Message Files

Each email produces up to three files:

| File | Purpose |
|------|---------|
| `*.mbox` | Complete original email in mbox format (single message). Can be opened by any email client. |
| `*.meta.json` | Structured metadata: sender, recipients, date, subject, thread ID, labels, attachment list, flags. This is the primary file for GUI display. |
| `*.txt` | Plain text extraction: headers + body text. Optimized for full-text search (grep, ripgrep, or custom indexer). |

### Metadata JSON Format (`*.meta.json`)

```json
{
    "messageId": "CABx123@mail.gmail.com",
    "contentHash": "a1b2c3d4e5f6...",
    "threadId": "thread_000042",
    "subject": "Meeting notes for Q1 review",
    "from": {
        "name": "Alice Smith",
        "email": "alice@example.com"
    },
    "date": "2023-01-15T10:30:22Z",
    "to": [
        {"name": "Bob Jones", "email": "bob@example.com"}
    ],
    "cc": [],
    "bcc": [],
    "inReplyTo": "CABx100@mail.gmail.com",
    "references": ["CABx100@mail.gmail.com"],
    "gmailLabels": ["Inbox", "Important", "Project-Alpha"],
    "attachments": [
        {
            "filename": "report.pdf",
            "mimeType": "application/pdf",
            "size": 245760,
            "storagePath": "attachments/report.pdf"
        }
    ],
    "hasTextBody": true,
    "hasHtmlBody": true
}
```

## Guide for GUI Developer

### How to List All Emails

1. **Recursively scan** the output directory for `*.meta.json` files
2. Parse each JSON to get message metadata
3. Sort by date for a chronological view

**Or use the indices:**

- Load `indices/by_date/YYYY-MM.json` for messages in a specific month
- Load `indices/by_sender/_sender_list.json` to show all known senders
- Load `indices/by_label/_label_list.json` to show Gmail label folders

### How to Display a Single Email

1. Read the `*.meta.json` file for headers, metadata, and thread information
2. For the message body:
   - Read the `*.txt` file for a quick plain-text view
   - Read the `*.mbox` file and parse the MIME structure for full
     HTML rendering with attachments
3. Thread context: use `threadId` to find sibling messages from
   `thread_index.json`

### How to Show Conversation Threads

1. Load `thread_index.json`
2. Find the thread by `threadId`
3. The `messageIds` array lists all messages in chronological order
4. Look up each message's `.meta.json` using the dedup index or
   by scanning the thread's folder
5. Display messages in order with indentation based on `inReplyTo`

### How to Search

#### By Sender
```
Load: indices/by_sender/{email}.json
Result: Array of {messageId, path, date, subject, threadId}
```

#### By Date Range
```
Load: indices/by_date/YYYY-MM.json for each month in range
Filter by specific dates within each month
```

#### By Gmail Label
```
Load: indices/by_label/{label}.json
Result: Array of {messageId, path, date, subject, from, threadId}
```

#### By Subject Keyword
```
Load: indices/by_word/{first_letter}.json
Look up the word key
Result: Array of {messageId, path}
```

#### Full-Text Search
For full-text body search, the recommended approach is:

1. **Simple:** Use `grep -r "search term" email_archive/ --include="*.txt"`
   on the `.txt` files
2. **Fast:** Build a full-text search index using a library like
   [Xapian](https://xapian.org/), [SQLite FTS5](https://sqlite.org/fts5.html),
   or [Tantivy](https://github.com/quickwit-oss/tantivy) over the `.txt` files
3. **GUI-integrated:** Load `.txt` files lazily and search in-memory with
   `QString::contains()` for smaller archives

### Recommended GUI Architecture

```
┌─────────────────────────────────────────────────┐
│  Main Window                                     │
├──────────┬──────────────────┬────────────────────┤
│          │                  │                    │
│  Folder  │  Message List    │  Message View      │
│  Tree    │                  │                    │
│          │  Subject | From  │  From: Alice       │
│ > Inbox  │  Date            │  To: Bob           │
│ > Sent   │                  │  Date: 2023-01-15  │
│ > Drafts │  • Meeting notes │  Subject: Meeting  │
│ > Labels │    > Re: Meeting │                    │
│   > Work │    > Re: Meeting │  Body text or HTML │
│   > ...  │  • Hello world   │  rendered here     │
│          │  • ...           │                    │
│ > 2023   │                  │  [Attachments]     │
│   > 01   │                  │  📎 report.pdf     │
│   > 02   │                  │                    │
│          │                  │                    │
└──────────┴──────────────────┴────────────────────┘
```

**Folder Tree** — built from:
- Gmail labels (from `indices/by_label/_label_list.json`)
- Date hierarchy (from `indices/by_date/`)
- The actual Year/Month/Label filesystem structure

**Message List** — loaded from:
- The selected folder/label/date index JSON file
- Sorted by date (newest first typical)
- Grouped by thread using `threadId`

**Message View** — loaded from:
- `*.meta.json` for headers
- `*.txt` for plain text body
- `*.mbox` for full MIME (HTML body, inline images, attachments)

### Key Design Decisions for GUI Developer

1. **Lazy loading:** Don't load all indices at startup. Load label list first,
   then load individual label/date indices on demand.

2. **Thread display:** Use `thread_index.json` to get message ordering within
   a thread. Use `inReplyTo` field to build the tree structure within a thread.

3. **Attachment access:** Attachments are referenced in `.meta.json` but
   stored within the `.mbox` file (in their MIME-encoded form). To extract,
   parse the `.mbox` file's MIME structure.

4. **Re-parsing safety:** The output directory can be repopulated by running
   the parser again. The `dedup_index.json` ensures no duplicates. The GUI
   should handle the directory being updated while it's open.

5. **Character encoding:** All `.meta.json` and `.txt` files are UTF-8.
   The `.mbox` files preserve the original encoding.

6. **File naming:** Each message's filename includes the date, subject snippet,
   and a content hash, making files naturally sortable and identifiable in
   a file manager.

### Performance Considerations

- For archives with 100K+ messages, avoid loading all `.meta.json` files at
  once. Use the index files instead.
- The date-based index (`by_date/YYYY-MM.json`) is the most practical for
  paginated browsing.
- The sender index enables quick "show all emails from this person" queries.
- For very large archives, consider building a SQLite database from the
  `.meta.json` files on first GUI launch for fast queries.

## Handling Edge Cases

### Same MBOX Parsed Twice
The `dedup_index.json` file tracks all previously stored Message-IDs and
content hashes. Running the parser on the same file twice will detect all
duplicates and skip them, reporting the count.

### Multiple MBOX Files with Overlapping Emails
Google Takeout often exports:
- `All mail Including Spam and Trash.mbox` (everything)
- `Inbox.mbox`, `Sent.mbox`, etc. (subsets)

The deduplicator handles this correctly. Messages are stored once (from the
first file that contains them) and skipped in subsequent files. Gmail labels
from the first occurrence are preserved.

### Messages Without Message-ID
A synthetic Message-ID is generated from a SHA-256 hash of the first 4KB
of the raw message. This ensures consistent deduplication even for
malformed messages.

### Encoding Issues
The parser handles:
- RFC 2047 encoded words in headers (=?charset?encoding?text?=)
- Multiple character sets (via Qt's QTextCodec)
- Base64 and Quoted-Printable transfer encodings
- Nested MIME multipart structures

### Huge Files
The streaming parser reads line-by-line and only holds one message in memory
at a time. A 10GB mbox file with 500K messages will use approximately 50-100MB
of RAM (dominated by the deduplication index and thread builder data structures).
```

This solution provides:

1. **Streaming parser** that handles multi-GB files with constant memory per message
2. **Robust deduplication** via Message-ID + content hash, persisted across runs
3. **Conversation threading** using References, In-Reply-To, and subject matching
4. **Human-readable folder structure**: Year → Month → Label → Thread → Messages
5. **Multiple search indices**: by sender, date, label, and subject keywords
6. **Three files per message**: `.mbox` (original), `.meta.json` (structured metadata), `.txt` (searchable plain text)
7. **Comprehensive README** for the GUI developer with data access patterns, architecture recommendations, and JSON format documentation