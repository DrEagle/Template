Template
========

An easy template class.

Creating a instance of The Template class.
$Template = new Template('test.html');

Adding a replace tag with it's replace value into the template file.
Template::addReplaceTag('content', 'some value for content');

Output the html file
$Template->Output();