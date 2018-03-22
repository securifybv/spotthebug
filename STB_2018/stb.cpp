[ .. includes .. ]

#define BUFSIZE    1024
#define MEMORYPORT 1337

#define PHPCOMMAND "php subprocess.php " STR(MEMORYPORT) " " STR(BUFSIZE)

class BaseCGIClass { int type; };
class Executor: public BaseCGIClass {
	public: virtual void exec(const char *prg) {
		std::system(prg);
	}
};
class Logger: public BaseCGIClass {
	public: virtual void logData(const char *data) {
		ofstream fout("/tmp/serverlog.txt");
		fout << data;
	}
};

void reply(char *type, char *content) {
	cout << "Content-type: " << type << "\r\n\r\n" << content << std::endl;
}

int main () {
	BaseCGIClass *base = new Executor();
	Cgicc formData;
	
	if (formData("encryptedData").length() > 0) {

		char *memorySpace = (char *)shmat(shmget(MEMORYPORT, BUFSIZE, IPC_CREAT | 0666), NULL, 0);
		strcpy (memorySpace, (char *)formData("encryptedData").c_str());
		static_cast<Executor*>(base)->exec(PHPCOMMAND);

		// Log errors (all errors start with a '#')
		if (memorySpace[0] == '#'){
			static_cast<Logger*>(base)->logData(memorySpace);
			reply((char *)"text/html", memorySpace);
		}
		else 
			reply((char *)"application/json", memorySpace);

		shmdt(memorySpace);
	}
	// This is a normal request, show warning
	else
		reply((char *)"text/html", (char *)"This is a secure endpoint, go away");

	return 0;
}