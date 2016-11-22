<%@ page import="java.util.Random" %>
<%@ page import="com.programmer.userfunctions.*" %>
<%@ taglib prefix="spring" uri="http://www.springframework.org/tags" %>
<!-- 
	These sessions need not be saved server-side!
	Example of an authorization cookie:
	amwwgcqpdtxthauepzxbjrropg-testuser	
	-->
	
<%!
userfunctions uf = new userfunctions();

public Boolean checkLogin(String username, String password)
{
	String loginResult = uf.getLoginSOAPXML(username,password);
	return loginResult.matches("^.*Authorized.*$");
}

public Boolean verifySession(String sess, String nonce)
{
	String username = sess.split("-")[1];
	if (createSession(nonce, username).equals(sess))
		return true; 
	else
		return false;
}

public String createSession(String nonce, String username)
{ 
	Long squaredNonce = getSquare(nonce);
	int UniqueUsernameNumber = getUniqueUsernameNumber(username);
	
	return generateSecret(squaredNonce + UniqueUsernameNumber) + "-" + username;
}

public Long getSquare(String nonce)
{
	long n = Long.parseLong(nonce);
	long squared = 0;
	for(int i = 0; i != (n * Math.abs(n)); i++)
	{
		if (i < 0)	i = 0;
		squared += 1;
	}
	return squared;
}

public String generateSecret(Long seed)
{
	Random gen = new Random(seed);
	String dat = "";
	for (int i = 0; i <= 25; i++)
		dat += (char)(gen.nextInt(25) + 97);	
	dat = uf.ROTWithSecret(dat);
	return dat;
}

public int getUniqueUsernameNumber(String username)
{
	int sum = 0;
	for (int i = 0; i <= username.length()-1; i++)
		sum += (int)username.charAt(i);
	return sum;
}
%>

<%
String username = request.getParameter("user");
String password = request.getParameter("pass");
String nonce = request.getParameter("nonce");
String message = "Login Error";
String data = "Userdata";
String ses = "";

try {
	Cookie[] cookies = request.getCookies();
	if (cookies != null)
		for (int i = 0; i < cookies.length; i++)
			if (cookies[i].getName().equals("session"))
				ses = cookies[i].getValue();
	
	if (!uf.isNullOrEmpty(nonce) && nonce.matches("([a-zA-Z]+)*"))
		message = "Nonce must be a number, not " + nonce;
	
	else if (!uf.isNullOrEmpty(ses) && !uf.isNullOrEmpty(nonce))
	{
		if (verifySession(ses, nonce))
		{
			message = "Welcome " + ses.split("-")[1];
			data = uf.getUserData(username);
		}
	}
	
	else if (!uf.isNullOrEmpty(username) && !uf.isNullOrEmpty(password) && !uf.isNullOrEmpty(nonce))
	{	
		Cookie c = new Cookie("session",createSession(nonce, username));
		c.setMaxAge(60*60*60*60);
		if (checkLogin(username,password))
		{
			response.addCookie(c);
			message = "Welcome";
			data = uf.getUserData(username);
		}
		else
			message = "Login error";
	}
	
}
catch (Exception e)
{
	message = "Login Error: " + e.getMessage();	
}
%>
<html><head><title>User page</title></head><body>
<%
if (message.contains("Welcome"))
{	%>
	Welcome <spring:message text="<%= username %>" /> <br> 
	<%= data %>
<% 
} 
else 
{	%>
	Error: <%= message %> <br>
	Please <a href="<%= request.getParameter("returnUrl") %>">return</a> to the login page.
<%
}	%>
</body></html>