--- 
title: Phoblogging
date: 2008-04-26 23:17:06
--- 
<h2>An apology</h2>
<p>
The sharp-eyed among you will have noticed that my blog has suddenly <a href="http://vhata.net/blog/2008/04/19/phoblog-lord-bo">become</a> one of <a href="http://images.google.com/images?q=my%20cat">those sites</a>.  I apologise for flooding your feed readers with pictures of seals, but let me explain.
</p>
<h2>A justification</h2>
<p>
The whole "let me post random photos from my life on my blog" thing was more an exercise in "how easy would it be to make a phoblog?" than a desire to share what my shoes look like.  I will admit that when I took the sunset photo, I thought "this would be a really good thing to share with the world", because, let's admit it, Cape Town is <a href="http://www.capetowndailyphoto.com/">one ridiculously beautiful city</a>, and people need to hear that.  But that got me thinking how easy it would be to make a photo shareable, and here is what I came up with.
</p>
<h2>An explanation</h2>
<p>
There is actually a function on <a href="http://www.sonyericsson.com/cws/products/mobilephones/overview/w850i">my phone</a> labelled "blog this", but I think it sends the image (or whatever) to a Sony-sponsored blogging site, and I'm frankly not interested in that.  I wanted to solve the problem academically, for the general case, and as a side effect solve it for my specific case - I run this blog in a <a href="http://drupal.org/">drupal</a> instance on my <a href="http://www.omnia.za.net/">own server</a> hosted with <a href="http://www.layeredtech.com/">Layered Tech</a>.
</p>
<h2>A discussion</h2>
<p>
So, the various ways to get information from my phone to my server were MMS, email, some form of push to a web-page, or bluetooth/cable upload to a laptop/desktop which will send it on.  The last option defeated the point - I wanted to be able to blog a photo from anywhere, using nothing but my phone.  Using the web-page push is what the "blog this" function does, but for my specific case, I'd have to write a custom application for the phone, which was way more effort than I wanted to expend.  Sending an MMS would require me to have a GSM modem listening somewhere to receive it, and had the added disadvantage of requiring that the images got resized down.  So, it seems, the best way to get the information from my phone to my server was to simply send an email (with images attached).
</p>
<h2>A technical discussion</h2>
<p>
The rest of this post describes the technical details of what happens to the email when it arrives at my server.
</p>
<p>
As an overview: I catch mail meant for the phoblog using a <a href="http://www.procmail.org">procmail</a> recipe, and pipe the mail to a python script, which parses the message and pulls out the relevant parts, constructing the body text, creating thumbnails of the images and saving them in the right place.  Having deconstructed the message and constructed the blog post, it passes the bits (title, body, and publication date, which it extracts from the EXIF information in the photos) to a PHP script, which hooks into the Drupal API and actually creates the blog post.
</p>
<p>
The PHP script is necessary, since there's no other way to hook into the Drupal API.  I could do something like faking a bunch of HTTP GETs and POSTs, and passing the information in as if I was actually blogging it from the web interface, but that's even more klunky than simply piping it into a PHP script.  The question then arises why I couldn't write the whole thing in PHP, and save myself the expense of running two scripts requiring two different interpreters, but frankly, trying to get PHP to do what is necessary would end in such an inelegant, ugly, hackish result that it just wouldn't be worth it.
</p>
<p>
An added advantage to separating the Python parser and the PHP script is that you can replace the PHP script with one that injects an entry into a different blogging platform, and it'll still work fine.  So, somebody could write a script that talks to Wordpress, and simply drop it into place. 
</p>
<hr />
<h2>The injector (the PHP script)</h2>
<p>
The PHP script needs to hook into the Drupal API, so we first need to bootstrap into the Drupal environment.  First we fake some HTTP headers in the $_SERVER array so that Drupal knows which site is being "requested" (Drupal does some clever multi-site stuff based on which URL is being requested).  Then we change to the Drupal base directory (defined as a constant at the top), include the bootstrap code (also defined at the top), and then simply run the <i>drupal_bootstrap()</i> function:
</p>
<code>
<span style="color: #000000">
<span style="color: #0000BB">&lt;?php&nbsp;<br /></span><span style="color: #FF8000">//&nbsp;Defined&nbsp;as&nbsp;a&nbsp;constant,&nbsp;could/should&nbsp;be&nbsp;passed&nbsp;as&nbsp;an&nbsp;option&nbsp;or&nbsp;loaded&nbsp;from&nbsp;a&nbsp;config&nbsp;file:<br /></span><span style="color: #0000BB">define</span><span style="color: #007700">(</span><span style="color: #DD0000">'PHOBLOG_DRUPAL_URI'</span><span style="color: #007700">,&nbsp;</span><span style="color: #DD0000">'http://vhata.net/'</span><span style="color: #007700">);<br /></span><span style="color: #FF8000">//&nbsp;Fairly&nbsp;standard&nbsp;for&nbsp;Drupal&nbsp;installations,&nbsp;but&nbsp;as&nbsp;above:<br /></span><span style="color: #0000BB">define</span><span style="color: #007700">(</span><span style="color: #DD0000">'PHOBLOG_DRUPAL_ROOT'</span><span style="color: #007700">,&nbsp;</span><span style="color: #DD0000">'/usr/share/drupal'</span><span style="color: #007700">);<br /></span><span style="color: #0000BB">define</span><span style="color: #007700">(</span><span style="color: #DD0000">'PHOBLOG_DRUPAL_BOOTSTRAP'</span><span style="color: #007700">,&nbsp;</span><span style="color: #DD0000">'includes/bootstrap.inc'</span><span style="color: #007700">);<br /><br /></span><span style="color: #FF8000">//&nbsp;Fake&nbsp;the&nbsp;necessary&nbsp;HTTP&nbsp;headers&nbsp;that&nbsp;Drupal&nbsp;needs:<br /></span><span style="color: #0000BB">$drupal_base_url&nbsp;</span><span style="color: #007700">=&nbsp;</span><span style="color: #0000BB">parse_url</span><span style="color: #007700">(</span><span style="color: #0000BB">PHOBLOG_DRUPAL_URI</span><span style="color: #007700">);<br /></span><span style="color: #0000BB">$_SERVER</span><span style="color: #007700">[</span><span style="color: #DD0000">'HTTP_HOST'</span><span style="color: #007700">]&nbsp;=&nbsp;</span><span style="color: #0000BB">$drupal_base_url</span><span style="color: #007700">[</span><span style="color: #DD0000">'host'</span><span style="color: #007700">];<br /></span><span style="color: #0000BB">$_SERVER</span><span style="color: #007700">[</span><span style="color: #DD0000">'PHP_SELF'</span><span style="color: #007700">]&nbsp;=&nbsp;</span><span style="color: #0000BB">$drupal_base_url</span><span style="color: #007700">[</span><span style="color: #DD0000">'path'</span><span style="color: #007700">].</span><span style="color: #DD0000">'/index.php'</span><span style="color: #007700">;<br /></span><span style="color: #0000BB">$_SERVER</span><span style="color: #007700">[</span><span style="color: #DD0000">'REQUEST_URI'</span><span style="color: #007700">]&nbsp;=&nbsp;</span><span style="color: #0000BB">$_SERVER</span><span style="color: #007700">[</span><span style="color: #DD0000">'SCRIPT_NAME'</span><span style="color: #007700">]&nbsp;=&nbsp;</span><span style="color: #0000BB">$_SERVER</span><span style="color: #007700">[</span><span style="color: #DD0000">'PHP_SELF'</span><span style="color: #007700">];<br /></span><span style="color: #0000BB">$_SERVER</span><span style="color: #007700">[</span><span style="color: #DD0000">'REMOTE_ADDR'</span><span style="color: #007700">]&nbsp;=&nbsp;</span><span style="color: #0000BB">NULL</span><span style="color: #007700">;<br /></span><span style="color: #0000BB">$_SERVER</span><span style="color: #007700">[</span><span style="color: #DD0000">'REQUEST_METHOD'</span><span style="color: #007700">]&nbsp;=&nbsp;</span><span style="color: #0000BB">NULL</span><span style="color: #007700">;<br /><br /></span><span style="color: #FF8000">//&nbsp;Change&nbsp;to&nbsp;Drupal&nbsp;root&nbsp;dir.<br /></span><span style="color: #0000BB">chdir</span><span style="color: #007700">(</span><span style="color: #0000BB">PHOBLOG_DRUPAL_ROOT</span><span style="color: #007700">);<br /><br />require_once(</span><span style="color: #0000BB">PHOBLOG_DRUPAL_BOOTSTRAP</span><span style="color: #007700">);<br /></span><span style="color: #0000BB">drupal_bootstrap</span><span style="color: #007700">(</span><span style="color: #0000BB">DRUPAL_BOOTSTRAP_FULL</span><span style="color: #007700">);<br /><br /></span><span style="color: #0000BB">?&gt;</span>
</span>
</code>
<p>
Now we are running in a Drupal environment.  The next step is to collect the information that we want to insert as a blog entry.  We take the title and publish date from the arguments passed to the script, and then do a loop to read the body from standard input:
</p>
<code><span style="color: #000000">
<span style="color: #0000BB">&lt;?php&nbsp;<br />$date&nbsp;</span><span style="color: #007700">=&nbsp;</span><span style="color: #0000BB">$_SERVER</span><span style="color: #007700">[</span><span style="color: #DD0000">'argv'</span><span style="color: #007700">][</span><span style="color: #0000BB">1</span><span style="color: #007700">];<br /></span><span style="color: #0000BB">$subject&nbsp;</span><span style="color: #007700">=&nbsp;</span><span style="color: #0000BB">$_SERVER</span><span style="color: #007700">[</span><span style="color: #DD0000">'argv'</span><span style="color: #007700">][</span><span style="color: #0000BB">2</span><span style="color: #007700">];<br /><br /></span><span style="color: #0000BB">$fp&nbsp;</span><span style="color: #007700">=&nbsp;</span><span style="color: #0000BB">fopen</span><span style="color: #007700">(</span><span style="color: #DD0000">'php://stdin'</span><span style="color: #007700">,&nbsp;</span><span style="color: #DD0000">'r'</span><span style="color: #007700">);<br /></span><span style="color: #0000BB">$body&nbsp;</span><span style="color: #007700">=&nbsp;</span><span style="color: #DD0000">""</span><span style="color: #007700">;<br />while(</span><span style="color: #0000BB">$line&nbsp;</span><span style="color: #007700">=&nbsp;</span><span style="color: #0000BB">fgets</span><span style="color: #007700">(</span><span style="color: #0000BB">$fp</span><span style="color: #007700">,&nbsp;</span><span style="color: #0000BB">4096</span><span style="color: #007700">))&nbsp;{<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #0000BB">$body&nbsp;</span><span style="color: #007700">.=&nbsp;</span><span style="color: #0000BB">$line</span><span style="color: #007700">;<br />}<br /><br /></span><span style="color: #0000BB">?&gt;</span>
</span>
</code>
<p>
I may be wrong, but I don't <b>think</b> there are any sanitization problems in the above code.  Let me know if you can see any?  I'm pretty sure I don't need to escape anything, since I pass all variables as-is to Drupal, which does full sanitization before using them.  Anyway, the final step is to simply call the drupal  <i>node_save()</i> function to save the blog post as a node (passing it some default values):
</p>
<code><span style="color: #000000">
<span style="color: #0000BB">&lt;?php&nbsp;<br />node_save</span><span style="color: #007700">((object)(array(</span><span style="color: #DD0000">'created'&nbsp;</span><span style="color: #007700">=&gt;&nbsp;</span><span style="color: #0000BB">$date</span><span style="color: #007700">,<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #DD0000">'title'&nbsp;</span><span style="color: #007700">=&gt;&nbsp;</span><span style="color: #0000BB">$subject</span><span style="color: #007700">,<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #DD0000">'body'&nbsp;</span><span style="color: #007700">=&gt;&nbsp;</span><span style="color: #0000BB">$body</span><span style="color: #007700">,<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #DD0000">'teaser'&nbsp;</span><span style="color: #007700">=&gt;&nbsp;</span><span style="color: #0000BB">$body</span><span style="color: #007700">,<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #DD0000">'format'&nbsp;</span><span style="color: #007700">=&gt;&nbsp;</span><span style="color: #DD0000">'3'</span><span style="color: #007700">,<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #DD0000">'uid'&nbsp;</span><span style="color: #007700">=&gt;&nbsp;</span><span style="color: #0000BB">1</span><span style="color: #007700">,<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #DD0000">'type'&nbsp;</span><span style="color: #007700">=&gt;&nbsp;</span><span style="color: #DD0000">'blog'</span><span style="color: #007700">,<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #DD0000">'status'&nbsp;</span><span style="color: #007700">=&gt;&nbsp;</span><span style="color: #0000BB">1</span><span style="color: #007700">,<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #DD0000">'comment'&nbsp;</span><span style="color: #007700">=&gt;&nbsp;</span><span style="color: #0000BB">2</span><span style="color: #007700">,<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #DD0000">'promote'&nbsp;</span><span style="color: #007700">=&gt;&nbsp;</span><span style="color: #0000BB">0</span><span style="color: #007700">,<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="color: #DD0000">'sticky'&nbsp;</span><span style="color: #007700">=&gt;&nbsp;</span><span style="color: #0000BB">0</span><span style="color: #007700">)));<br /><br /></span><span style="color: #0000BB">?&gt;</span>
</span>
</code>
<p>
My only worry there is that I specify that the format is type 3 (unfiltered HTML) - this might leave the phoblogger open to code-injection exploits.  I should probably specify type 1, filtered HTML, to make sure that nobody can accidentally blog something nasty.
</p>
<p>
So, that's the PHP script that injects the entry into Drupal.  The other part of the system is, of course, the Python script that parses the email in the first place.
</p>
<h2>The parser (the Python script)</h2>
<p>
I'm not posting the full script here, for a number of reasons, mostly to do with it "not being finished yet".  It works, but it doesn't do everything it should (including a complete security check, since that's kinda hard to implement on emails, which can be faked).  Suffice it to say, it uses the <b>optparse</b>, <b>ConfigParser</b> and <b>logging</b> modules to be nicely configurable, runnable, and debuggable, and all that.  But, yeah, I'm still embarrassed about it, and won't post sourcecode until I think it's good-looking enough for public consumption.  What I will post here is bits of python code that demonstrate the actual meat of the thing - how I deconstruct and process the email that I receive.
</p>
<p>
The basic steps I perform are:
<ol>
<li>Break up the email and extract the bits I need from it.</li>
<li>Process each attachment part:
  <ul>
    <li>Text attachments get HTML-ified</li>
    <li>HTML attachments get inserted as-is</li>
    <li>Image attachments get thumbnailed, and the thumbnails and originals get stored somewhere web-accessible, and a chunk of HTML that references them gets created.</li>
  </ul></li>
<li>Send the results of this processing to the injector script with the right subject and date.</li>
</ol>
Breaking up the email is trivial using the <b>email</b> module in python:
</p>
<p>
<code>
<span style="color: #008000; font-weight: bold">import</span> <span style="color: #0000FF; font-weight: bold">email</span><br />
msg <span style="color: #666666">=</span> email<span style="color: #666666">.</span>message_from_file(sys<span style="color: #666666">.</span>stdin)<br />
subject <span style="color: #666666">=</span> <span style="color: #BA2121">u&#39;&#39;</span><span style="color: #666666">.</span>join(<span style="color: #008000">unicode</span>(part, encoding <span style="color: #AA22FF; font-weight: bold">or</span> <span style="color: #BA2121">&#39;us-ascii&#39;</span>) <span style="color: #008000; font-weight: bold">for</span> part, encoding <span style="color: #AA22FF; font-weight: bold">in</span> email<span style="color: #666666">.</span>header<span style="color: #666666">.</span>decode_header(msg<span style="color: #666666">.</span>get(<span style="color: #BA2121">&#39;subject&#39;</span>)))<br />
msgfrom <span style="color: #666666">=</span> email<span style="color: #666666">.</span>utils<span style="color: #666666">.</span>getaddresses([msg<span style="color: #666666">.</span>get(<span style="color: #BA2121">&#39;from&#39;</span>)])[<span style="color: #666666">0</span>][<span style="color: #666666">1</span>]<br />
msgid <span style="color: #666666">=</span> msg<span style="color: #666666">.</span>get(<span style="color: #BA2121">&#39;message-id&#39;</span>)<br /><br />
<span style="color: #008000; font-weight: bold">for</span> piece <span style="color: #AA22FF; font-weight: bold">in</span> msg<span style="color: #666666">.</span>get_payload():<br />
&nbsp;&nbsp;&nbsp;</span>processpiece(piece)<br />
</code>
</p>
<p>
As you can see, no regular expressions needed to match headers, do MIME decoding, or break up an email address.  You can even give it a list of all the different stupid formats for addresses that mail clients seem to use these days, and it will understand them:
</p>
<code>&gt;&gt;&gt; getaddresses([<span style="color: #BA2121">&quot;jonathan@vhata.net&quot;</span>, <span style="color: #BA2121">&#39;&quot;Jonathan III&quot; &lt;vhata@clug.org.za&gt;&#39;</span>, <span style="color: #BA2121">&#39;pope@vatican.org (Benedict)&#39;</span>])<br />[(<span style="color: #BA2121">&#39;&#39;</span>, <span style="color: #BA2121">&#39;jonathan@vhata.net&#39;</span>),<br />&nbsp;(<span style="color: #BA2121">&#39;Jonathan III&#39;</span>, <span style="color: #BA2121">&#39;vhata@clug.org.za&#39;</span>),<br />&nbsp;(<span style="color: #BA2121">&#39;Benedict&#39;</span>, <span style="color: #BA2121">&#39;pope@vatican.org&#39;</span>)]<br />
</code>
<p>
I break each attachment up and send them to the <i>processpiece()</i> function one at a time.
</p>
<p>
Inside the <i>processpiece()</i> function, I can get at the content-type of the chunk I'm processing by using the <i>get_content_type()</i> method:
</p>
<code>
&gt;&gt;&gt; piece<span style="color: #666666">.</span>get_content_type()<br />
'image/jpeg'<br />
&gt;&gt;&gt; piece<span style="color: #666666">.</span>get_content_maintype()<br />
'image'<br />
&gt;&gt;&gt; piece<span style="color: #666666">.</span>get_content_subtype()<br />
'jpeg'<br />
</code>
<p>
and I can use this to work out what I want to do with the chunk.  I can also get the chunk in its raw form (i.e. decoded from the MIME transport that email uses by simply calling <i>get_payload()</i> on it:
</p>
<code>
payload <span style="color: #666666">=</span> piece<span style="color: #666666">.</span>get_payload(decode<span style="color: #666666">=</span><span style="color: #008000">True</span>)<br />
</code>
<p>
If it's text, I simply replace all the newlines with HTML line breaks:
</p>
<code>
payload<span style="color: #666666">.</span>replace(<span style="color: #BA2121">&quot;</span><span style="color: #BB6622; font-weight: bold">\n</span><span style="color: #BA2121">&quot;</span>,<span style="color: #BA2121">&quot;&lt;br /&gt;</span><span style="color: #BB6622; font-weight: bold">\n</span><span style="color: #BA2121">&quot;</span>)<br />
</code>
<p>
The difficult case is, of course, when it's an image.  Here, I use the <a href="http://www.pythonware.com/products/pil/">Python Imaging Library</a> to process the image. I extract the EXIF timestamp and turn into a datetime structure, so that I can create a hierarchical directory tree to store the images.  Then, I construct a thumbnail filename and create the thumbnail:
</p>
<code>
payload <span style="color: #666666">=</span> piece<span style="color: #666666">.</span>get_payload(decode<span style="color: #666666">=</span><span style="color: #008000">True</span>)<br />
image <span style="color: #666666">=</span> Image<span style="color: #666666">.</span>open(StringIO<span style="color: #666666">.</span>StringIO(payload))<br /><br />
timestamp <span style="color: #666666">=</span> datetime<span style="color: #666666">.</span>datetime<span style="color: #666666">.</span>strptime(image<span style="color: #666666">.</span>_getexif()[EXIF_DATETIME], <span style="color: #BA2121">&quot;%Y:%m:</span><span style="color: #BB6688; font-weight: bold">%d</span><span style="color: #BA2121"> %H:%M:%S&quot;</span>)<br />
<span style="color: #008000">self</span><span style="color: #666666">.</span>entrystamp <span style="color: #666666">=</span> timestamp<br /><br />
targetdir <span style="color: #666666">=</span> <span style="color: #BA2121">&quot;</span><span style="color: #BB6688; font-weight: bold">%04d</span><span style="color: #BA2121">/</span><span style="color: #BB6688; font-weight: bold">%02d</span><span style="color: #BA2121">/</span><span style="color: #BB6688; font-weight: bold">%02d</span><span style="color: #BA2121">&quot;</span> <span style="color: #666666">%</span> (timestamp<span style="color: #666666">.</span>year, timestamp<span style="color: #666666">.</span>month, timestamp<span style="color: #666666">.</span>day)<br />
<span style="color: #008000; font-weight: bold">try</span>:<br />
&nbsp;&nbsp;&nbsp;os<span style="color: #666666">.</span>makedirs(<span style="color: #BA2121">&quot;</span><span style="color: #BB6688; font-weight: bold">%s</span><span style="color: #BA2121">/</span><span style="color: #BB6688; font-weight: bold">%s</span><span style="color: #BA2121">&quot;</span> <span style="color: #666666">%</span> (TARGETDIR, targetdir), <span style="color: #666666">0755</span>)<br />
<span style="color: #008000; font-weight: bold">except</span> <span style="color: #D2413A; font-weight: bold">OSError</span>:<br />
&nbsp;&nbsp;&nbsp;<span style="color: #008000; font-weight: bold">pass</span><br /><br />
fname <span style="color: #666666">=</span> piece<span style="color: #666666">.</span>get_filename()<br />
(rootname, ext) <span style="color: #666666">=</span> os<span style="color: #666666">.</span>path<span style="color: #666666">.</span>splitext(fname)<br />
ext <span style="color: #666666">=</span> ext<span style="color: #666666">.</span>lower()<br />
fname <span style="color: #666666">=</span> <span style="color: #BA2121">&quot;</span><span style="color: #BB6688; font-weight: bold">%s%s</span><span style="color: #BA2121">&quot;</span> <span style="color: #666666">%</span> (rootname, ext)<br />
thumbname <span style="color: #666666">=</span> <span style="color: #BA2121">&quot;</span><span style="color: #BB6688; font-weight: bold">%s</span><span style="color: #BA2121">-thumb</span><span style="color: #BB6688; font-weight: bold">%s</span><span style="color: #BA2121">&quot;</span> <span style="color: #666666">%</span> (rootname, ext)<br /><br />
image<span style="color: #666666">.</span>save(<span style="color: #BA2121">&quot;</span><span style="color: #BB6688; font-weight: bold">%s</span><span style="color: #BA2121">/</span><span style="color: #BB6688; font-weight: bold">%s</span><span style="color: #BA2121">/</span><span style="color: #BB6688; font-weight: bold">%s</span><span style="color: #BA2121">&quot;</span> <span style="color: #666666">%</span> (TARGETDIR, targetdir, fname))<br />
os<span style="color: #666666">.</span>chmod(<span style="color: #BA2121">&quot;</span><span style="color: #BB6688; font-weight: bold">%s</span><span style="color: #BA2121">/</span><span style="color: #BB6688; font-weight: bold">%s</span><span style="color: #BA2121">/</span><span style="color: #BB6688; font-weight: bold">%s</span><span style="color: #BA2121">&quot;</span> <span style="color: #666666">%</span> (TARGETDIR, targetdir, fname), <span style="color: #666666">0644</span>)<br />
image2 <span style="color: #666666">=</span> image<span style="color: #666666">.</span>copy()<br />
image2<span style="color: #666666">.</span>thumbnail([THUMBSIZE,THUMBSIZE])<br />
image2<span style="color: #666666">.</span>save(<span style="color: #BA2121">&quot;</span><span style="color: #BB6688; font-weight: bold">%s</span><span style="color: #BA2121">/</span><span style="color: #BB6688; font-weight: bold">%s</span><span style="color: #BA2121">/</span><span style="color: #BB6688; font-weight: bold">%s</span><span style="color: #BA2121">&quot;</span> <span style="color: #666666">%</span> (TARGETDIR, targetdir, thumbname))<br />
os<span style="color: #666666">.</span>chmod(<span style="color: #BA2121">&quot;</span><span style="color: #BB6688; font-weight: bold">%s</span><span style="color: #BA2121">/</span><span style="color: #BB6688; font-weight: bold">%s</span><span style="color: #BA2121">/</span><span style="color: #BB6688; font-weight: bold">%s</span><span style="color: #BA2121">&quot;</span> <span style="color: #666666">%</span> (TARGETDIR, targetdir, thumbname), <span style="color: #666666">0644</span>)<br />
</code>
<p>
Then I return a templated chunk of text to dump into the blog post.  Easy as pie.
</p>
<p>
The last step is to pipe the individually formatted pieces to the injector script, passing it the date (extracted from the EXIF information above) and subject as parameters:
</p>
<code>
injector <span style="color: #666666">=</span> subprocess<span style="color: #666666">.</span>Popen([ADDCMD, entrystamp<span style="color: #666666">.</span>strftime(<span style="color: #BA2121">&quot;</span><span style="color: #BB6688; font-weight: bold">%s</span><span style="color: #BA2121">&quot;</span>), <span style="color: #BA2121">&quot;Phoblog: </span><span style="color: #BB6688; font-weight: bold">%s</span><span style="color: #BA2121">&quot;</span> <span style="color: #666666">%</span> subject],stdin<span style="color: #666666">=</span>subprocess<span style="color: #666666">.</span>PIPE)<br />                <span style="color: #008000; font-weight: bold">for</span> piece <span style="color: #AA22FF; font-weight: bold">in</span> body:<br />
&nbsp;&nbsp;&nbsp;injector<span style="color: #666666">.</span>stdin<span style="color: #666666">.</span>write(piece)<br />injector<span style="color: #666666">.</span>communicate()<br />
</code>
<p>
And off it goes.
</p>
<h2>Some concerns</h2>
<p>
First and foremost, security is a problem.   If I'm sending an email from my phone, anybody can send the same email from their own phone - there is no identification in the email.  One way around this would be to require a keyword in the subject before accepting it.  This is security by obscurity - anybody who gets hold of the keyword will be in.  I can decrease this risk by forcing some sort of hash on the keyword.  For example, if the keyword was "pilates", I could require that the number of consonants in the current day be appended to that:  "pilates6" on a Sunday, "pilates7" on a Tuesday.  This slightly decreases the risk, but not much.  There are other, even cleverer variations on this theme, but they are all basically just security by obscurity.  A better way would be to use authenticated SMTP, and only accept phoblog messages that were authenticated through my own SMTP server, and I think I might implement this, unless I can think of a flaw in the idea.
</p>
<p>
Another problem is that I might lay myself open to HTML/javascript/etc injections, but I think this will be allayed if I solve the problem above.
</p>
<h2>A conclusion</h2>
<p>
This has been a somewhat rambling, somewhat disjointed explication, but I hope it gives you the general gist of what I did.  If I ever look at the script again, maybe I'll fix it up properly, and make it publicly available.  I even registered <strong>phoblog.za.net</strong> but that's <a href="http://www.za.net/cgi-bin/status.cgi?domain=phoblog.za.net">taking some time</a>.  Meantime, enjoy piccies.
</p>
