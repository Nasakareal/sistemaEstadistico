import re
import sys
import zipfile

from docx import Document
from docx.oxml import OxmlElement


source_path, output_path = sys.argv[1], sys.argv[2]
invalid_ampersand = re.compile(rb"&(?!amp;|lt;|gt;|quot;|apos;|#[0-9]+;|#x[0-9A-Fa-f]+;)")

with zipfile.ZipFile(source_path, "r") as source:
    with zipfile.ZipFile(output_path, "w") as output:
        for item in source.infolist():
            data = source.read(item.filename)
            if item.filename.endswith(".xml") or item.filename.endswith(".rels"):
                data = invalid_ampersand.sub(b"&amp;", data)
            if item.filename == "word/document.xml":
                data = data.replace(b"C102&amp;", b"C-1028")
            output.writestr(item, data)


document = Document(output_path)
main_table = document.tables[-1]

for index, row in enumerate(main_table.rows):
    row_properties = row._tr.get_or_add_trPr()

    if row_properties.find("{http://schemas.openxmlformats.org/wordprocessingml/2006/main}cantSplit") is None:
        row_properties.append(OxmlElement("w:cantSplit"))

    if index == 0 and row_properties.find("{http://schemas.openxmlformats.org/wordprocessingml/2006/main}tblHeader") is None:
        row_properties.append(OxmlElement("w:tblHeader"))

document.save(output_path)
